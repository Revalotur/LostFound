import os
import re
import threading
import numpy as np
import cv2
import base64
import json
from datetime import datetime
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
# Batasi CORS hanya ke origin yang dikenal
CORS(app, origins=['http://localhost', 'http://localhost:80', 'http://localhost:5000', 'http://127.0.0.1'])

# API Key sederhana untuk autentikasi internal
API_KEY = os.environ.get('FACE_API_KEY', 'lostfound-internal-key-ganti')

# Konfigurasi direktori
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EMBEDDINGS_DIR = os.path.join(BASE_DIR, 'embeddings')
TEMP_DIR = os.path.join(BASE_DIR, 'temp')
MODELS_DIR = os.path.join(BASE_DIR, 'models')

# Buat direktori jika tidak ada
os.makedirs(EMBEDDINGS_DIR, exist_ok=True)
os.makedirs(TEMP_DIR, exist_ok=True)
os.makedirs(MODELS_DIR, exist_ok=True)

# Load InsightFace model (diinisialisasi saat startup, fallback lazy jika perlu)
face_analyzer = None
model_load_lock = threading.Lock()
model_load_error = None

def require_api_key():
    """Dekorator untuk mengecek API Key"""
    api_key = request.headers.get('X-API-Key') or (request.get_json(silent=True) or {}).get('api_key')
    if api_key != API_KEY:
        return jsonify({'success': False, 'message': 'Unauthorized'}), 401
    return None

def initialize_face_analyzer():
    """Inisialisasi model InsightFace. Aman dipanggil berkali-kali (idempoten + thread-safe)."""
    global face_analyzer, model_load_error
    with model_load_lock:
        if face_analyzer is not None:
            return True
        if model_load_error is not None:
            # Sudah pernah gagal, jangan ulangi load yang berat
            raise RuntimeError(model_load_error)

        try:
            # Validasi keberadaan file model sebelum load
            model_dir = os.path.join(MODELS_DIR, 'models', 'buffalo_sc')
            required_files = ['det_500m.onnx', 'w600k_mbf.onnx']
            missing = [f for f in required_files if not os.path.exists(os.path.join(model_dir, f))]
            if missing:
                raise FileNotFoundError(
                    "File model InsightFace tidak ditemukan: " + ", ".join(missing) +
                    f" (diharapkan di {model_dir})"
                )

            from insightface.app import FaceAnalysis
            face_analyzer = FaceAnalysis(
                name='buffalo_sc',
                providers=['CPUExecutionProvider'],
                root=MODELS_DIR
            )
            face_analyzer.prepare(ctx_id=0, det_size=(320, 320))
            model_load_error = None
            print("InsightFace model loaded successfully!")
            return True
        except Exception as e:
            err_msg = f"InsightFace model gagal di-load: {type(e).__name__}: {e}"
            print(err_msg)
            face_analyzer = None
            model_load_error = err_msg
            raise RuntimeError(err_msg)

def ensure_model_ready():
    """Helper untuk route: pastikan model siap, kembalikan (ok, error_message)."""
    if face_analyzer is None:
        try:
            initialize_face_analyzer()
        except RuntimeError as e:
            return False, str(e)
    return True, None

def sanitize_user_id(user_id):
    """Cegah path traversal pada user_id"""
    sanitized = re.sub(r'[^a-zA-Z0-9_\-]', '', str(user_id))
    if sanitized != str(user_id) or not sanitized:
        raise ValueError("Invalid user_id")
    return sanitized

def base64_to_image(base64_string):
    """Konversi base64 string ke gambar OpenCV"""
    if ',' in base64_string:
        base64_string = base64_string.split(',')[1]
    
    img_data = base64.b64decode(base64_string)
    nparr = np.frombuffer(img_data, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    return img

def get_face_embedding(img):
    """Dapatkan embedding wajah dari gambar"""
    ok, err = ensure_model_ready()
    if not ok:
        return None, f"Model tidak siap: {err}"
    
    faces = face_analyzer.get(img)
    
    if len(faces) == 0:
        return None, "No face detected"
    
    if len(faces) > 1:
        return None, "Multiple faces detected"
    
    # Ambil face pertama dan embeddingnya
    face = faces[0]
    embedding = face.normed_embedding.tolist()
    
    return embedding, "Success"

def compute_eye_variance(img, face):
    if not hasattr(face, 'kps') or face.kps is None:
        return None

    kps = face.kps
    eye_dist = np.linalg.norm(kps[1] - kps[0])
    crop_w = max(int(eye_dist * 0.25), 10)
    crop_h = max(int(eye_dist * 0.15), 8)

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    def region_variance(center):
        cx, cy = int(round(center[0])), int(round(center[1]))
        x1 = max(0, cx - crop_w // 2)
        x2 = min(img.shape[1], cx + crop_w // 2)
        y1 = max(0, cy - crop_h // 2)
        y2 = min(img.shape[0], cy + crop_h // 2)
        region = gray[y1:y2, x1:x2]
        if region.size < 10:
            return 0.0
        return float(np.std(region))

    left_var = region_variance(kps[0])
    right_var = region_variance(kps[1])

    return (left_var + right_var) / 2


def cosine_similarity(a, b):
    """Hitung cosine similarity antara dua vektor"""
    a = np.array(a)
    b = np.array(b)
    return np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b))

@app.route('/health', methods=['GET'])
def health():
    """Endpoint untuk cek service berjalan & status model"""
    model_status = 'loaded' if face_analyzer is not None else (
        'error' if model_load_error is not None else 'not_loaded'
    )
    return jsonify({
        'status': 'ok',
        'model_status': model_status,
        'model_error': model_load_error,
        'timestamp': datetime.now().isoformat()
    })

@app.route('/register-face', methods=['POST'])
def register_face():
    """Endpoint untuk mendaftarkan wajah baru"""
    auth_error = require_api_key()
    if auth_error:
        return auth_error

    try:
        data = request.get_json()
        
        if not data or 'image' not in data or 'user_id' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing required fields: image and user_id'
            }), 400
        
        try:
            user_id = sanitize_user_id(data['user_id'])
        except ValueError:
            return jsonify({'success': False, 'message': 'Invalid user_id format'}), 400

        image_base64 = data['image']
        
        # Konversi base64 ke gambar
        img = base64_to_image(image_base64)
        if img is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image data'
            }), 400
        
        # Dapatkan embedding
        embedding, message = get_face_embedding(img)
        
        if embedding is None:
            return jsonify({
                'success': False,
                'message': message
            }), 400
        
        # Simpan embedding ke file JSON
        embedding_file = os.path.join(EMBEDDINGS_DIR, f'{user_id}.json')
        
        embedding_data = {
            'user_id': user_id,
            'embedding': embedding,
            'registered_at': datetime.now().isoformat()
        }
        
        with open(embedding_file, 'w') as f:
            json.dump(embedding_data, f)
        
        return jsonify({
            'success': True,
            'message': 'Face registered successfully',
            'data': {
                'user_id': user_id,
                'embedding': embedding,
                'registered_at': embedding_data['registered_at']
            }
        })
        
    except Exception as e:
        print(f"Error in register-face: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/verify-face', methods=['POST'])
def verify_face():
    """Endpoint untuk memverifikasi wajah dengan yang sudah terdaftar"""
    auth_error = require_api_key()
    if auth_error:
        return auth_error

    try:
        data = request.get_json()
        
        if not data or 'image' not in data or 'user_id' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing required fields: image and user_id'
            }), 400
        
        try:
            user_id = sanitize_user_id(data['user_id'])
        except ValueError:
            return jsonify({'success': False, 'message': 'Invalid user_id format'}), 400

        image_base64 = data['image']
        
        # Cek apakah user sudah terdaftar
        embedding_file = os.path.join(EMBEDDINGS_DIR, f'{user_id}.json')
        
        if not os.path.exists(embedding_file):
            return jsonify({
                'success': False,
                'message': 'Face not registered. Please register first.'
            }), 404
        
        # Load embedding yang sudah terdaftar
        with open(embedding_file, 'r') as f:
            stored_data = json.load(f)
        
        stored_embedding = stored_data['embedding']
        
        # Konversi base64 ke gambar
        img = base64_to_image(image_base64)
        if img is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image data'
            }), 400
        
        # Dapatkan embedding dari gambar baru
        current_embedding, message = get_face_embedding(img)
        
        if current_embedding is None:
            return jsonify({
                'success': False,
                'message': message
            }), 400
        
        # Hitung similarity
        similarity = cosine_similarity(stored_embedding, current_embedding)
        
        # Threshold similarity (biasanya 0.4-0.6 untuk InsightFace)
        threshold = 0.4
        is_verified = similarity >= threshold
        
        return jsonify({
            'success': True,
            'verified': is_verified,
            'similarity': float(similarity),
            'threshold': threshold,
            'message': 'Face verified successfully' if is_verified else 'Face does not match'
        })
        
    except Exception as e:
        print(f"Error in verify-face: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/detect-face', methods=['POST'])
def detect_face():
    """Endpoint untuk mendeteksi keberadaan wajah dalam gambar (untuk challenge)"""
    auth_error = require_api_key()
    if auth_error:
        return auth_error

    try:
        data = request.get_json()
        
        if not data or 'image' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing required field: image'
            }), 400
        
        image_base64 = data['image']
        
        # Konversi base64 ke gambar
        img = base64_to_image(image_base64)
        if img is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image data'
            }), 400
        
        ok, err = ensure_model_ready()
        if not ok:
            return jsonify({
                'success': False,
                'message': f'Layanan face recognition tidak siap: {err}'
            }), 503
        
        faces = face_analyzer.get(img)
        
        if len(faces) == 0:
            return jsonify({
                'success': True,
                'face_detected': False,
                'message': 'No face detected'
            })
        
        face = faces[0]
        bbox = face.bbox.tolist() if face.bbox is not None else None
        kps = face.kps.tolist() if face.kps is not None else None
        
        return jsonify({
            'success': True,
            'face_detected': True,
            'data': {
                'bbox': bbox,
                'kps': kps,
                'face_count': len(faces)
            }
        })
        
    except Exception as e:
        print(f"Error in detect-face: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/detect-blink', methods=['POST'])
def detect_blink():
    auth_error = require_api_key()
    if auth_error:
        return auth_error

    try:
        data = request.get_json()

        if not data or 'images' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing required field: images'
            }), 400

        images = data['images']
        if not isinstance(images, list) or len(images) < 3:
            return jsonify({
                'success': False,
                'message': 'Need at least 3 images'
            }), 400

        ok, err = ensure_model_ready()
        if not ok:
            return jsonify({
                'success': False,
                'message': f'Layanan face recognition tidak siap: {err}'
            }), 503

        var_values = []
        for img_b64 in images:
            img = base64_to_image(img_b64)
            if img is None:
                var_values.append(None)
                continue

            faces = face_analyzer.get(img)
            if len(faces) == 0:
                var_values.append(None)
                continue

            face = faces[0]
            variance = compute_eye_variance(img, face)
            var_values.append(variance)

        valid_vars = [v for v in var_values if v is not None]

        if len(valid_vars) < 3:
            return jsonify({
                'success': True,
                'blink_detected': False,
                'var_values': var_values,
                'face_detected': len(valid_vars) > 0,
                'message': 'Not enough frames with face detected'
            })

        baseline = sum(valid_vars[:5]) / len(valid_vars[:5])
        blink_detected = False

        for var in valid_vars:
            if var < 8.0 or (baseline > 12.0 and var < baseline * 0.4):
                blink_detected = True
                break

        return jsonify({
            'success': True,
            'blink_detected': blink_detected,
            'var_values': var_values,
            'face_detected': True,
            'message': 'Blink detected' if blink_detected else 'No blink detected'
        })

    except Exception as e:
        print(f"Error in detect-blink: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


if __name__ == '__main__':
    print("Starting Face Verification Service...")
    print(f"API Key: {API_KEY[:16]}... (gunakan header X-API-Key)")

    # Warm-up model saat startup agar tidak gagal pada request pertama
    try:
        initialize_face_analyzer()
        print("Model siap digunakan.")
    except RuntimeError as e:
        print("=" * 60)
        print("PERINGATAN: Model face recognition GAGAL di-load saat startup!")
        print(str(e))
        print("Service tetap berjalan, tapi endpoint face akan mengembalikan 503.")
        print("Pastikan file model ada di folder 'models/models/buffalo_sc/'")
        print("dan dependency (insightface, onnxruntime, opencv) terinstal.")
        print("=" * 60)

    # Hanya bind ke localhost untuk keamanan
    app.run(host='127.0.0.1', port=5000, debug=False)

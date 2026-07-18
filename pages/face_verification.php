<?php
// pages/face_verification.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../auth/login.php', 'Please login first', 'danger');
}

// Jika sudah verified, redirect ke halaman utama atau sesuai redirect_to
if (is_face_verified($conn)) {
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '../index.php';
    redirect($redirect_to, 'You are already verified', 'success');
}

include '../includes/header.php';
?>

<div class="container" style="max-width: 900px; margin-top: 40px;">
    <div class="detail-container animate-fade-in" style="display: block; padding: 40px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px; color: var(--text);">
                <i data-lucide="shield-check" style="width: 40px; height: 40px; vertical-align: middle; margin-right: 10px; color: var(--primary);"></i>
                Face Verification
            </h1>
            <p style="color: var(--text-light); font-size: 1.1rem;">
                Verifikasi wajah Anda untuk mengakses fitur chat
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
            <div>
                <div style="position: relative; background: var(--bg); border-radius: 16px; overflow: hidden; border: 2px solid var(--border);">
                    <video id="webcam" autoplay playsinline style="width: 100%; display: block; transform: scaleX(-1);"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <div id="scan-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: none; align-items: center; justify-content: center; background: rgba(99, 102, 241, 0.1);">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; animation: pulse 1.5s infinite;">
                                <i data-lucide="scan-face"></i>
                            </div>
                            <p style="margin-top: 10px; color: var(--primary); font-weight: 600;">Scanning...</p>
                        </div>
                    </div>
                </div>
                
                <div id="status-message" style="margin-top: 20px; padding: 15px; border-radius: 12px; text-align: center; font-weight: 600;">
                    <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle; margin-right: 5px;"></i>
                    <span id="status-text">Aktifkan webcam untuk memulai</span>
                </div>
            </div>

            <div>
                <div style="background: var(--bg); padding: 25px; border-radius: 16px; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 20px; color: var(--text); display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="list-checks" style="color: var(--primary);"></i>
                        Challenge Steps
                    </h3>
                    
                    <div id="challenge-steps">
                        <div class="challenge-step" data-step="1" style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border);">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                1
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Hadap Lurus</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Posisikan wajah Anda menghadap kamera</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>

                        <div class="challenge-step" data-step="2" style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border); opacity: 0.5;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                2
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Kedipkan Mata</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Kedipkan mata Anda beberapa kali</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>

                        <div class="challenge-step" data-step="3" style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border); opacity: 0.5;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                3
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Hadap Kiri</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Posisikan wajah menghadap ke kiri</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>

                        <div class="challenge-step" data-step="4" style="display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border); opacity: 0.5;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                4
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Hadap Kanan</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Posisikan wajah menghadap ke kanan</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button id="start-btn" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.1rem;" disabled>
                        <i data-lucide="play" style="width: 20px; height: 20px;"></i>
                        Mulai Verifikasi
                    </button>
                    <button id="stop-btn" class="btn btn-block" style="padding: 15px; font-size: 1.1rem; display: none; margin-top: 10px; background: var(--secondary); color: white;">
                        <i data-lucide="square" style="width: 20px; height: 20px;"></i>
                        Berhenti
                    </button>
                </div>
            </div>
        </div>

        <div id="result-section" style="display: none; text-align: center; padding: 40px; background: var(--bg); border-radius: 16px; margin-top: 30px;">
            <div id="result-icon" style="font-size: 5rem; margin-bottom: 20px;"></div>
            <h2 id="result-title" style="margin-bottom: 10px;"></h2>
            <p id="result-message" style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 30px;"></p>
            <a id="result-btn" href="../index.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">
                Lanjutkan
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    .challenge-step.active {
        border-color: var(--primary) !important;
        opacity: 1 !important;
    }
    
    .challenge-step.active .step-icon {
        background: var(--primary) !important;
        color: white !important;
    }
    
    .challenge-step.completed {
        border-color: var(--success) !important;
        opacity: 1 !important;
    }
    
    .challenge-step.completed .step-icon {
        background: var(--success) !important;
        color: white !important;
    }
    
    #status-message.info {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }
    
    #status-message.success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    
    #status-message.error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }
</style>

<script>
    let video = document.getElementById('webcam');
    let canvas = document.getElementById('canvas');
    let ctx = canvas.getContext('2d');
    let stream = null;
    let isVerifying = false;
    let currentStep = 1;
    let challengeCompleted = [];
    let stepFrames = {}; // menyimpan data deteksi per step
    
    let faceLandmarker = null;
    let faceLandmarkerReady = false;
    
    const LEFT_EYE = [33, 159, 158, 133, 153, 144];
    const RIGHT_EYE = [362, 385, 387, 263, 373, 380];
    
    function computeEAR(landmarks) {
        const d = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
        const l = LEFT_EYE.map(i => landmarks[i]);
        const r = RIGHT_EYE.map(i => landmarks[i]);
        const earL = (d(l[1], l[5]) + d(l[2], l[4])) / (2 * d(l[0], l[3]));
        const earR = (d(r[1], r[5]) + d(r[2], r[4])) / (2 * d(r[0], r[3]));
        return (earL + earR) / 2;
    }
    
    async function initFaceLandmarker() {
        const MP_BASE = '<?php echo BASE_URL; ?>assets/mediapipe/';
        try {
            updateStatus('Memuat model deteksi wajah (lokal), harap tunggu...', 'info');
            const { FilesetResolver, FaceLandmarker } = await import(MP_BASE + 'vision_bundle.mjs');
            const vision = await FilesetResolver.forVisionTasks(MP_BASE + 'wasm/');
            faceLandmarker = await FaceLandmarker.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: MP_BASE + 'face_landmarker.task',
                    delegate: 'CPU'
                },
                runningMode: 'IMAGE',
                numFaces: 1
            });
            faceLandmarkerReady = true;
            console.log('MediaPipe FaceLandmarker ready');
            document.getElementById('start-btn').disabled = false;
            if (stream) {
                updateStatus('Webcam aktif! Tekan "Mulai Verifikasi" untuk memulai', 'info');
            } else {
                updateStatus('Model siap. Izinkan akses webcam untuk memulai.', 'info');
            }
        } catch (err) {
            console.error('FaceLandmarker init failed:', err);
            faceLandmarkerReady = false;
            updateStatus('Gagal memuat model deteksi dari server lokal. Klik untuk coba lagi.', 'error');
            const sm = document.getElementById('status-message');
            if (sm) sm.style.cursor = 'pointer';
        }
    }
    
    const challenges = [
        { id: 1, name: 'Hadap Lurus', instruction: 'Posisikan wajah Anda menghadap kamera' },
        { id: 2, name: 'Kedipkan Mata', instruction: 'Kedipkan mata Anda beberapa kali' },
        { id: 3, name: 'Hadap Kiri', instruction: 'Posisikan wajah menghadap ke kiri' },
        { id: 4, name: 'Hadap Kanan', instruction: 'Posisikan wajah menghadap ke kanan' }
    ];

    async function startWebcam() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                } 
            });
            video.srcObject = stream;
            canvas.width = 640;
            canvas.height = 480;
            updateStatus('Memuat model deteksi wajah, harap tunggu...', 'info');
        } catch (err) {
            updateStatus('Gagal mengakses webcam: ' + err.message, 'error');
            console.error(err);
        }
    }

    function updateStatus(message, type = 'info') {
        const statusEl = document.getElementById('status-message');
        const textEl = document.getElementById('status-text');
        textEl.textContent = message;
        statusEl.className = type;
        
        const icon = statusEl.querySelector('[data-lucide]');
        if (icon) {
            if (type === 'success') icon.setAttribute('data-lucide', 'check-circle');
            else if (type === 'error') icon.setAttribute('data-lucide', 'alert-circle');
            else icon.setAttribute('data-lucide', 'info');
            lucide.createIcons();
        }
    }

    function updateStep(step, status) {
        const stepEl = document.querySelector(`.challenge-step[data-step="${step}"]`);
        if (!stepEl) return;
        
        stepEl.classList.remove('active', 'completed');
        
        if (status === 'active') {
            stepEl.classList.add('active');
        } else if (status === 'completed') {
            stepEl.classList.add('completed');
            const statusIcon = stepEl.querySelector('.step-status');
            statusIcon.innerHTML = '<i data-lucide="check"></i>';
            lucide.createIcons();
        }
        
        document.querySelectorAll('.challenge-step').forEach(el => {
            const elStep = parseInt(el.dataset.step);
            if (elStep > step && status === 'active') {
                el.style.opacity = '0.5';
            } else if (elStep <= step) {
                el.style.opacity = '1';
            }
        });
    }

    function captureFrame() {
        ctx.save();
        ctx.scale(-1, 1);
        ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
        ctx.restore();
        return canvas.toDataURL('image/jpeg', 0.8);
    }

    async function detectFace(imageData) {
        try {
            const response = await fetch('<?php echo BASE_URL; ?>api/detect_face_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: imageData, csrf_token: csrfToken })
            });
            return await response.json();
        } catch (err) {
            console.error('Detection error:', err);
            return { success: false };
        }
    }

    function getFaceCenter(faceData) {
        if (!faceData || !faceData.bbox) return null;
        const bbox = faceData.bbox;
        return {
            cx: (bbox[0] + bbox[2]) / 2,
            cy: (bbox[1] + bbox[3]) / 2,
            width: bbox[2] - bbox[0],
            height: bbox[3] - bbox[1]
        };
    }

    function distance(a, b) {
        return Math.sqrt((a.cx - b.cx) ** 2 + (a.cy - b.cy) ** 2);
    }

    async function detectBlink() {
        updateStatus('Kedipkan mata Anda beberapa kali...', 'info');
        const earValues = [];
        for (let i = 0; i < 12; i++) {
            captureFrame();
            try {
                const result = faceLandmarker.detect(canvas);
                if (result.faceLandmarks && result.faceLandmarks.length > 0) {
                    earValues.push(computeEAR(result.faceLandmarks[0]));
                } else {
                    earValues.push(null);
                }
            } catch {
                earValues.push(null);
            }
            await new Promise(r => setTimeout(r, 60));
        }
        const valid = earValues.filter(v => v !== null);
        if (valid.length < 3) {
            return { success: true, blink_detected: false, ear_values: earValues, face_detected: valid.length > 0 };
        }
        const baseline = valid.slice(0, 5).reduce((a, b) => a + b, 0) / Math.min(5, valid.length);
        let blinkDetected = false;
        for (const ear of valid) {
            if (ear < 0.20 || (baseline > 0.2 && ear < baseline * 0.55)) {
                blinkDetected = true;
                break;
            }
        }
        return { success: true, blink_detected: blinkDetected, ear_values: earValues, face_detected: true };
    }

    async function runVerification() {
        isVerifying = true;
        currentStep = 1;
        challengeCompleted = [];
        stepFrames = {};
        
        if (!faceLandmarkerReady) {
            updateStatus('Model deteksi wajah belum siap. Tunggu beberapa saat atau klik pesan ini untuk coba lagi.', 'error');
            stopVerification();
            return;
        }
        document.getElementById('start-btn').style.display = 'none';
        document.getElementById('stop-btn').style.display = 'block';
        document.getElementById('scan-overlay').style.display = 'flex';
        
        // Step 1: Hadap Lurus — deteksi wajah saja
        if (isVerifying) {
            updateStep(1, 'active');
            updateStatus(challenges[0].instruction, 'info');
            await new Promise(r => setTimeout(r, 1000));
            const img = captureFrame();
            const result = await detectFace(img);
            if (result.success && result.face_detected && result.data) {
                stepFrames[1] = getFaceCenter(result.data);
                challengeCompleted.push(1);
                updateStep(1, 'completed');
                updateStatus('Wajah terdeteksi!', 'success');
            } else {
                stopVerification();
                updateStatus('Wajah tidak terdeteksi. Pastikan wajah Anda terlihat jelas.', 'error');
            }
        }

        // Step 2: Kedip — deteksi perubahan frame
        if (isVerifying && challengeCompleted.length === 1) {
            updateStep(2, 'active');
            let blinkSuccess = false;
            let blinkAttempts = 0;
            const maxBlinkAttempts = 3;
            while (!blinkSuccess && blinkAttempts < maxBlinkAttempts && isVerifying) {
                blinkAttempts++;
                updateStatus(`Kedipkan mata Anda beberapa kali (percobaan ${blinkAttempts}/${maxBlinkAttempts})...`, 'info');
                const result = await detectBlink();
                if (result.success && result.blink_detected) {
                    blinkSuccess = true;
                } else {
                    const earText = result.ear_values ? result.ear_values.filter(v => v !== null).map(v => v.toFixed(2)).join(', ') : 'N/A';
                    console.log('EAR values:', earText);
                    if (blinkAttempts < maxBlinkAttempts) {
                        updateStatus(`Kedipan belum terdeteksi (EAR: ${earText}). Coba lagi...`, 'info');
                        await new Promise(r => setTimeout(r, 800));
                    }
                }
            }
            if (blinkSuccess) {
                challengeCompleted.push(2);
                updateStep(2, 'completed');
                updateStatus('Kedipan terdeteksi!', 'success');
                await new Promise(r => setTimeout(r, 500));
            } else {
                stopVerification();
                updateStatus('Kedipan tidak terdeteksi setelah beberapa percobaan. Pastikan mata Anda terlihat dan kedipkan secara alami.', 'error');
            }
        }

        // Step 3: Hadap Kiri
        if (isVerifying && challengeCompleted.length === 2) {
            updateStep(3, 'active');
            updateStatus(challenges[2].instruction, 'info');
            await new Promise(r => setTimeout(r, 1500));
            const img = captureFrame();
            const result = await detectFace(img);
            if (result.success && result.face_detected && result.data) {
                stepFrames[3] = getFaceCenter(result.data);
                // Cek pergerakan ke kiri: face center x harus lebih kecil dari step 1
                if (stepFrames[1] && stepFrames[3].cx < stepFrames[1].cx - 10) {
                    challengeCompleted.push(3);
                    updateStep(3, 'completed');
                    updateStatus('Hadap kiri terdeteksi!', 'success');
                } else {
                    stopVerification();
                    updateStatus('Posisi wajah tidak berubah. Putar wajah ke kiri Anda.', 'error');
                }
            } else {
                stopVerification();
                updateStatus('Wajah tidak terdeteksi. Pastikan wajah Anda terlihat.', 'error');
            }
        }

        // Step 4: Hadap Kanan
        if (isVerifying && challengeCompleted.length === 3) {
            updateStep(4, 'active');
            updateStatus(challenges[3].instruction, 'info');
            await new Promise(r => setTimeout(r, 1500));
            const img = captureFrame();
            const result = await detectFace(img);
            if (result.success && result.face_detected && result.data) {
                stepFrames[4] = getFaceCenter(result.data);
                // Cek pergerakan ke kanan: face center x harus lebih besar dari step 1
                if (stepFrames[1] && stepFrames[4].cx > stepFrames[1].cx + 10) {
                    challengeCompleted.push(4);
                    updateStep(4, 'completed');
                    updateStatus('Hadap kanan terdeteksi!', 'success');
                } else {
                    stopVerification();
                    updateStatus('Posisi wajah tidak berubah. Putar wajah ke kanan Anda.', 'error');
                }
            } else {
                stopVerification();
                updateStatus('Wajah tidak terdeteksi. Pastikan wajah Anda terlihat.', 'error');
            }
        }
        
        if (isVerifying && challengeCompleted.length === 4) {
            document.getElementById('scan-overlay').style.display = 'none';
            updateStatus('Menyimpan data wajah...', 'info');
            
            const imageData = captureFrame();
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>api/register_face.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: imageData, csrf_token: csrfToken })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResult(true, 'Verifikasi Berhasil!', 'Wajah Anda telah terverifikasi. Sekarang Anda dapat mengakses fitur chat.');
                } else {
                    showResult(false, 'Verifikasi Gagal', result.message || 'Terjadi kesalahan saat verifikasi. Silakan coba lagi.');
                }
            } catch (err) {
                showResult(false, 'Verifikasi Gagal', 'Terjadi kesalahan: ' + err.message);
            }
        }
        
        stopVerification();
    }

    function stopVerification() {
        isVerifying = false;
        document.getElementById('scan-overlay').style.display = 'none';
        document.getElementById('start-btn').style.display = 'block';
        document.getElementById('stop-btn').style.display = 'none';
    }

    function showResult(success, title, message) {
        const resultSection = document.getElementById('result-section');
        const resultIcon = document.getElementById('result-icon');
        const resultTitle = document.getElementById('result-title');
        const resultMessage = document.getElementById('result-message');
        const resultBtn = document.getElementById('result-btn');
        
        resultSection.style.display = 'block';
        
        if (success) {
            resultIcon.innerHTML = '<i data-lucide="check-circle-2" style="color: var(--success); width: 80px; height: 80px;"></i>';
            resultTitle.style.color = 'var(--success)';
        } else {
            resultIcon.innerHTML = '<i data-lucide="x-circle" style="color: var(--danger); width: 80px; height: 80px;"></i>';
            resultTitle.style.color = 'var(--danger)';
        }
        
        resultTitle.textContent = title;
        resultMessage.textContent = message;
        
        const redirectTo = '<?php echo isset($_GET["redirect_to"]) ? $_GET["redirect_to"] : "../index.php"; ?>';
        resultBtn.href = redirectTo;
        
        lucide.createIcons();
    }

    // Event listeners
    document.getElementById('start-btn').addEventListener('click', runVerification);
    document.getElementById('stop-btn').addEventListener('click', () => {
        stopVerification();
        updateStatus('Verifikasi dihentikan', 'info');
        for (let i = 1; i <= 4; i++) {
            updateStep(i, '');
        }
        updateStep(1, 'active');
    });
    // Klik pesan status untuk mengulang inisialisasi model jika gagal
    document.getElementById('status-message').addEventListener('click', () => {
        if (!faceLandmarkerReady) {
            initFaceLandmarker();
        }
    });

    // Initialize
    startWebcam();
    initFaceLandmarker();
</script>

<?php include '../includes/footer.php'; ?>

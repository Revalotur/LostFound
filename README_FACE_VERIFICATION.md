# Face Verification Feature Documentation

## Overview
Fitur Face Verification telah ditambahkan ke aplikasi LostFound sebagai syarat untuk menggunakan fitur Private Room Chat. Verifikasi wajah tidak diperlukan untuk registrasi atau penggunaan fitur dasar lainnya.

## File Structure

### New Files Created
1. **`face_service/`** - Direktori untuk Flask Face Verification Service
   - `app.py` - Aplikasi Flask utama dengan endpoint untuk face verification
   - `requirements.txt` - Daftar dependensi Python
   - `models/` - Direktori untuk menyimpan model InsightFace (auto-download)
   - `embeddings/` - Direktori untuk menyimpan face embeddings
   - `temp/` - Direktori temporary

2. **`api/register_face.php`** - Endpoint PHP untuk mendaftarkan wajah pengguna
3. **`api/create_room.php`** - Endpoint PHP untuk membuat chat room
4. **`api/send_message.php`** - Endpoint PHP untuk mengirim pesan
5. **`api/get_messages.php`** - Endpoint PHP untuk mengambil pesan

6. **`pages/face_verification.php`** - Halaman face verification dengan webcam
7. **`pages/chat.php`** - Halaman private room chat

8. **`database_migration.sql`** - SQL migration untuk update database

### Modified Files
1. **`database.sql`** - Updated dengan tabel baru dan kolom tambahan
2. **`includes/functions.php`** - Ditambahkan helper functions untuk Flask API communication
3. **`pages/detail.php`** - Ditambahkan badge verifikasi dan tombol chat
4. **`pages/profile.php`** - Ditambahkan status verifikasi dan tombol verifikasi
5. **`index.php`** - Ditambahkan badge verifikasi di setiap posting

## Database Changes

### New Column in `users` Table
- `face_verified` (BOOLEAN, DEFAULT FALSE) - Status verifikasi wajah pengguna

### New Tables
1. **`face_verifications`** - Menyimpan riwayat verifikasi wajah
   - `id` (INT, PRIMARY KEY)
   - `user_id` (INT, FOREIGN KEY)
   - `face_embedding` (TEXT)
   - `verified_at` (TIMESTAMP)
   - `created_at` (TIMESTAMP)

2. **`chat_rooms`** - Menyimpan room chat
   - `id` (INT, PRIMARY KEY)
   - `report_id` (INT, FOREIGN KEY)
   - `initiator_id` (INT, FOREIGN KEY)
   - `owner_id` (INT, FOREIGN KEY)
   - `created_at` (TIMESTAMP)

3. **`chat_messages`** - Menyimpan pesan chat
   - `id` (INT, PRIMARY KEY)
   - `room_id` (INT, FOREIGN KEY)
   - `sender_id` (INT, FOREIGN KEY)
   - `message` (TEXT)
   - `is_read` (BOOLEAN, DEFAULT FALSE)
   - `created_at` (TIMESTAMP)

## Flask API Endpoints

### 1. GET /health
Memeriksa apakah Flask service berjalan dengan baik.

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2026-07-01T10:00:00"
}
```

### 2. POST /register-face
Mendaftarkan wajah pengguna baru.

**Request Body:**
```json
{
  "user_id": 1,
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Face registered successfully",
  "data": {
    "user_id": 1,
    "registered_at": "2026-07-01T10:00:00"
  }
}
```

### 3. POST /verify-face
Membandingkan wajah baru dengan embedding yang tersimpan.

**Request Body:**
```json
{
  "user_id": 1,
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Response (Success):**
```json
{
  "success": true,
  "verified": true,
  "similarity": 0.75,
  "threshold": 0.4,
  "message": "Face verified successfully"
}
```

### 4. POST /detect-face
Mendeteksi keberadaan wajah dalam gambar.

**Request Body:**
```json
{
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Response (Success):**
```json
{
  "success": true,
  "face_detected": true,
  "data": {
    "bbox": [x1, y1, x2, y2],
    "landmarks": [...],
    "face_count": 1
  }
}
```

## Installation & Setup

### 1. Database Setup
Jika database sudah ada, jalankan migration:
```bash
mysql -u root -p lost_and_found_db < database_migration.sql
```

Atau import `database.sql` untuk database baru.

### 2. Python Dependencies Installation
Pindah ke direktori `face_service` dan install dependensi:
```bash
cd face_service
pip install -r requirements.txt
```

**Dependencies:**
- `flask==3.0.0` - Web framework
- `flask-cors==4.0.0` - CORS support
- `insightface==0.7.3` - Face recognition library
- `opencv-python==4.8.1.78` - Image processing
- `numpy==1.24.3` - Numerical computing
- `onnxruntime==1.16.3` - ONNX model runtime
- `Pillow==10.1.0` - Image handling

### 3. Run Flask Service
Di direktori `face_service`:
```bash
python app.py
```
Service akan berjalan di `http://localhost:5000`

### 4. Run PHP Application
Pastikan Apache/Nginx berjalan dan arahkan ke direktori project.

## Usage Flow

### User Registration & Login
1. Pengguna mendaftar akun seperti biasa
2. Pengguna login ke aplikasi

### Browsing & Posting
- Pengguna dapat melihat semua posting
- Pengguna dapat membuat posting barang hilang/ditemukan
- **Tidak perlu verifikasi wajah** untuk fitur ini

### Using Chat Feature
1. Pengguna melihat posting dan klik "Hubungi Pemilik"
2. Sistem memeriksa status `face_verified`:
   - Jika **TRUE**: Langsung masuk ke room chat
   - Jika **FALSE**: Redirect ke halaman Face Verification
3. Pengguna melakukan verifikasi wajah dengan 4 langkah challenge:
   - Hadap lurus
   - Kedip
   - Hadap kiri
   - Hadap kanan
4. Setelah verifikasi berhasil, status akun menjadi "Identity Verified"
5. Pengguna dapat langsung masuk ke room chat

## Badge Display

### Verified User
Menampilkan badge hijau dengan teks "Identity Verified" dan icon check.

### Unverified User
Menampilkan badge abu-abu dengan teks "Not Verified" dan icon circle.

## Security Features

1. **File Validation**: Validasi tipe dan ukuran file gambar
2. **Temporary File Cleanup**: File temporary dihapus setelah selesai
3. **Prepared Statements**: Semua query database menggunakan prepared statements
4. **Input Sanitization**: Semua input pengguna disanitasi
5. **Session-based Access**: Endpoint hanya dapat diakses oleh user yang login

## Tech Stack

- **Backend**: PHP (existing), Python Flask (new)
- **Face Recognition**: InsightFace
- **Image Processing**: OpenCV, Pillow
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript, SweetAlert2

## Testing Guide

### 1. Test Flask Service
1. Buka browser atau Postman
2. Akses `http://localhost:5000/health`
3. Seharusnya menampilkan status "ok"

### 2. Test Face Verification
1. Login ke aplikasi
2. Klik tombol "Hubungi Pemilik" di salah satu posting
3. Seharusnya redirect ke halaman face verification
4. Izinkan akses webcam
5. Klik "Mulai Verifikasi"
6. Ikuti instruksi challenge
7. Setelah selesai, status akan menjadi "Identity Verified"

### 3. Test Chat Feature
1. Pastikan kedua pengguna sudah verifikasi wajah
2. Klik "Hubungi Pemilik" dari akun berbeda
3. Coba kirim pesan
4. Pesan seharusnya muncul secara real-time (polling every 3 seconds)

## Notes

- InsightFace akan otomatis mendownload model saat pertama kali dijalankan
- Pastikan port 5000 tidak terblokir
- Untuk production, sebaiknya gunakan WSGI server seperti Gunicorn
- Face similarity threshold dapat diubah di `face_service/app.py`

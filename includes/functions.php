<?php
// includes/functions.php

// Konfigurasi Flask Face Service
define('FLASK_API_URL', 'http://127.0.0.1:5000');
define('FLASK_API_KEY', 'lostfound-internal-key-ganti');
define('DEVELOPMENT_MODE', false); // Ubah ke false untuk production
define('FLASK_API_TIMEOUT', 60);   // detik, cukup untuk load model pertama kali

/**
 * Membersihkan input dari karakter berbahaya
 */
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

/**
 * Escape output untuk HTML (shortcut)
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi kekuatan password: min 8 karakter, huruf besar, huruf kecil, angka
 * Return true jika valid, string error jika tidak
 */
function validate_password_strength($password) {
    if (strlen($password) < 8) {
        return 'Password minimal 8 karakter.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password harus mengandung huruf besar.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password harus mengandung huruf kecil.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password harus mengandung angka.';
    }
    return true;
}

/**
 * Escape output untuk HTML, dengan allowance tag <strong>
 */
function e_html($str) {
    return strip_tags($str ?? '', '<strong>');
}

/**
 * Validasi file upload — MIME type, ekstensi, dan ukuran
 * Return: [true, $new_filename] atau [false, $error_message]
 */
function validate_upload($file, $max_size = 2097152) {
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Gagal mengupload file.'];
    }

    if ($file['size'] < 1) {
        return [false, 'File kosong.'];
    }

    if ($file['size'] > $max_size) {
        return [false, 'Ukuran file maksimal ' . ($max_size / 1048576) . 'MB.'];
    }

    if (!in_array($ext, $allowed_exts)) {
        return [false, 'Format file tidak didukung (Gunakan: JPG, PNG, WEBP).'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes)) {
        return [false, 'Tipe file tidak valid. Hanya gambar yang diperbolehkan.'];
    }

    $new_filename = uniqid('IMG_', true) . '.' . $ext;
    $destination = UPLOAD_DIR . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [false, 'Gagal menyimpan file.'];
    }

    return [true, $new_filename];
}

/**
 * Cek apakah user sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Cek apakah user sudah verifikasi wajah
 */
function is_face_verified($conn, $user_id = null) {
    if ($user_id === null) {
        if (!isset($_SESSION['user_id'])) return false;
        $user_id = $_SESSION['user_id'];
    }
    
    $stmt = $conn->prepare("SELECT face_verified FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    return $user ? (bool)$user['face_verified'] : false;
}

/**
 * Cek apakah layanan Flask face recognition berjalan & model siap.
 * Mengembalikan array ['up' => bool, 'model_status' => string, 'message' => string]
 */
function flask_service_status() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, FLASK_API_URL . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $http_code != 200) {
        return [
            'up' => false,
            'model_status' => 'down',
            'message' => 'Layanan face recognition tidak berjalan. Jalankan face_service/start_service.bat terlebih dahulu.'
        ];
    }

    $result = json_decode($response, true);
    $model_status = $result['model_status'] ?? 'unknown';
    if ($model_status !== 'loaded') {
        return [
            'up' => true,
            'model_status' => $model_status,
            'message' => 'Layanan berjalan tetapi model wajah gagal di-load: ' . ($result['model_error'] ?? 'unknown error')
        ];
    }

    return ['up' => true, 'model_status' => 'loaded', 'message' => 'ok'];
}

/**
 * Kirim request ke Flask Face API
 */
function call_flask_api($endpoint, $data = [], $method = 'POST') {
    $url = FLASK_API_URL . $endpoint;
    
    $ch = curl_init();
    
    $json_data = json_encode($data);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data),
        'X-API-Key: ' . FLASK_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, FLASK_API_TIMEOUT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'message' => 'Connection error: ' . $error,
            'http_code' => 0
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid response from server',
            'http_code' => $http_code,
            'raw_response' => $response
        ];
    }
    
    return array_merge($result, ['http_code' => $http_code]);
}

/**
 * Register wajah user ke Flask API
 */
function register_face($conn, $user_id, $image_base64) {
    // DEVELOPMENT MODE: langsung berhasil tanpa Flask
    if (DEVELOPMENT_MODE) {
        error_log("DEVELOPMENT MODE: Skipping Flask API call");
        
        // Update status di database
        $stmt = $conn->prepare("UPDATE users SET face_verified = TRUE WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Catat di face_verifications
        $stmt = $conn->prepare("INSERT INTO face_verifications (user_id, verified_at) VALUES (?, NOW())");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Face registered successfully (DEVELOPMENT MODE)'
        ];
    }
    
    $status = flask_service_status();
    if (!$status['up'] || $status['model_status'] !== 'loaded') {
        return [
            'success' => false,
            'message' => $status['message']
        ];
    }

    $response = call_flask_api('/register-face', [
        'user_id' => $user_id,
        'image' => $image_base64
    ]);
    
    if (!$response['success']) {
        return $response;
    }
    
    // Update status di database
    $stmt = $conn->prepare("UPDATE users SET face_verified = TRUE WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Ambil embedding dari response Flask (jika ada)
    $embedding_json = null;
    if (isset($response['data']['embedding'])) {
        $embedding_json = json_encode($response['data']['embedding']);
    }
    
    // Catat di face_verifications beserta embedding
    $stmt = $conn->prepare("INSERT INTO face_verifications (user_id, face_embedding, verified_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $embedding_json);
    $stmt->execute();
    
    return [
        'success' => true,
        'message' => 'Face registered and verified successfully'
    ];
}

/**
 * Verify wajah user
 */
function verify_face($user_id, $image_base64) {
    return call_flask_api('/verify-face', [
        'user_id' => $user_id,
        'image' => $image_base64
    ]);
}

/**
 * Detect face (untuk challenge)
 */
function detect_face($image_base64) {
    return call_flask_api('/detect-face', [
        'image' => $image_base64
    ]);
}

function detect_blink($images) {
    return call_flask_api('/detect-blink', [
        'images' => $images
    ]);
}

/**
 * Generate CSRF token dan simpan di session
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate hidden input field untuk CSRF
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validasi CSRF token dari request
 */
function verify_csrf($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect dengan pesan (flash message logic simple)
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = [
            'text' => $message,
            'type' => $type
        ];
    }
    header("Location: " . $url);
    exit();
}

/**
 * Tampilkan pesan flash jika ada
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = ($msg['type'] === 'success') ? 'success' : 'error';
        $text = addslashes($msg['text']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '" . ($type === 'success' ? 'Berhasil!' : 'Ops!') . "',
                    text: '{$text}',
                    icon: '{$type}',
                    confirmButtonColor: '#6366f1',
                    borderRadius: '1rem'
                });
            });
        </script>";
        unset($_SESSION['flash_message']);
    }
}

/**
 * Kirim email notifikasi.
 */
function send_email_notification($to, $subject, $message) {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("send_email_notification: alamat email tidak valid: {$to}");
        return false;
    }

    $headers = "From: LostFound <no-reply@lostfound.local>\r\n";
    $headers .= "Reply-To: no-reply@lostfound.local\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $body = '<html><body>';
    $body .= '<div style="font-family: Arial, sans-serif; color: #111;">';
    $body .= "<h2 style=\"color:#2563eb;\">{$subject}</h2>";
    $body .= "<div style=\"font-size: 16px; line-height: 1.6;\">{$message}</div>";
    $body .= '<p style="margin-top: 2rem; color:#6b7280;">LostFound System</p>';
    $body .= '</div>';
    $body .= '</body></html>';

    // TODO: Ganti dengan PHPMailer untuk production (SMTP):
    // require_once __DIR__ . '/../vendor/autoload.php';
    // $mail = new PHPMailer\PHPMailer\PHPMailer();
    // $mail->isSMTP(); $mail->Host = 'smtp.example.com'; ...
    $success = mail($to, $subject, $body, $headers);
    if (!$success) {
        error_log("send_email_notification gagal mengirim ke {$to}");
    }
    return $success;
}

/**
 * Cari laporan matching untuk notifikasi laporan baru.
 */
function get_matching_reports_for_new_report($conn, $item_name, $current_id, $category = null, $type = null) {
    $keywords = explode(' ', $item_name);
    $query_parts = [];
    foreach ($keywords as $word) {
        $word = trim($word);
        if (strlen($word) > 2) {
            $query_parts[] = "r.item_name LIKE '%" . $conn->real_escape_string($word) . "%'";
        }
    }

    if (empty($query_parts)) return [];

    $sql = "SELECT r.*, u.email, u.username FROM reports r JOIN users u ON r.user_id = u.id WHERE (" . implode(' OR ', $query_parts);

    if ($category) {
        $sql .= " OR r.category = '" . $conn->real_escape_string($category) . "'";
    }

    $sql .= ") AND r.id != ? AND r.status = 'open'";

    if ($type) {
        $sql .= " AND r.type != '" . $conn->real_escape_string($type) . "'";
    }

    $sql .= " LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function notify_matching_users_on_new_report($conn, $new_report_id, $item_name, $type, $category, $location) {
    $matches = get_matching_reports_for_new_report($conn, $item_name, $new_report_id, $category, $type);
    if (empty($matches)) {
        return;
    }

    $type_label = $type === 'lost' ? 'barang hilang' : 'barang ditemukan';
    $report_url = BASE_URL . 'pages/detail.php?id=' . $new_report_id;

    foreach ($matches as $match) {
        if (!filter_var($match['email'], FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $subject = 'Ada laporan baru yang mungkin cocok dengan barang Anda';
        $message = "Halo " . htmlspecialchars($match['username']) . ",<br><br>";
        $message .= "Kami menemukan laporan baru untuk <strong>{$item_name}</strong> yang mungkin cocok dengan laporan Anda.<br>";
        $message .= "Jenis laporan: <strong>{$type_label}</strong><br>";
        $message .= "Lokasi: <strong>{$location}</strong><br>";
        $message .= "Kategori: <strong>" . htmlspecialchars($category) . "</strong><br><br>";
        $message .= "Lihat detail laporan baru di sini: <a href=\"{$report_url}\">{$report_url}</a><br><br>";
        $message .= "Terima kasih telah menggunakan LostFound.";

        send_email_notification($match['email'], $subject, $message);
    }
}

function send_report_resolved_email($conn, $report_id) {
    $stmt = $conn->prepare("SELECT r.item_name, r.type, u.email, u.username FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();

    if (!$report || !filter_var($report['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $type_label = $report['type'] === 'lost' ? 'barang hilang' : 'barang ditemukan';
    $subject = 'Laporan Anda Telah Ditandai Selesai';
    $message = "Halo " . htmlspecialchars($report['username']) . ",<br><br>";
    $message .= "Laporan Anda untuk <strong>" . htmlspecialchars($report['item_name']) . "</strong> sebagai <strong>{$type_label}</strong> telah ditandai sebagai selesai.<br>";
    $message .= "Jika laporan ini belum benar-benar selesai, silakan periksa kembali di panel LostFound.<br><br>";
    $message .= "Terima kasih telah menggunakan LostFound.";

    return send_email_notification($report['email'], $subject, $message);
}

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);           // value 60 is seconds  
    $hours   = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec  
    $days    = round($seconds / 86400);        // value 86400 is 24 hours * 60 minutes * 60 sec  
    $weeks   = round($seconds / 604800);       // value 604800 is 7 days * 24 hours * 60 minutes * 60 sec  
    $months  = round($seconds / 2629440);      // value 2629440 is ((365+365+366+365+365)/5/12) * 24 * 60 * 60  
    $years   = round($seconds / 31553280);     // value 31553280 is (365+365+366+365+365)/5 * 24 * 60 * 60  
    
    if ($seconds <= 60) {
        return "Baru saja";
    } else if ($minutes <= 60) {
        return "$minutes menit yang lalu";
    } else if ($hours <= 24) {
        return "$hours jam yang lalu";
    } else if ($days <= 7) {
        return "$days hari yang lalu";
    } else if ($weeks <= 4.3) {
        return "$weeks minggu yang lalu";
    } else if ($months <= 12) {
        return "$months bulan yang lalu";
    } else {
        return "$years tahun yang lalu";
    }
}

/**
 * Fungsi matching sederhana untuk mencari barang yang mirip
 */
function get_matching_reports($conn, $item_name, $current_id, $category = null) {
    $keywords = explode(' ', $item_name);
    $query_parts = [];
    foreach ($keywords as $word) {
        if (strlen($word) > 2) {
            $query_parts[] = "item_name LIKE '%" . $conn->real_escape_string($word) . "%'";
        }
    }

    if (empty($query_parts)) return [];

    $sql = "SELECT * FROM reports WHERE (" . implode(' OR ', $query_parts);
    
    if ($category) {
        $sql .= " OR category = '" . $conn->real_escape_string($category) . "'";
    }
    
    $sql .= ") AND id != ? AND status = 'open' LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Buat notifikasi di database
 */
function create_notification($conn, $user_id, $report_id, $message, $type = 'match') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, report_id, type, message) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iiss", $user_id, $report_id, $type, $message);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get semua notifikasi user yang belum dibaca
 */
function get_unread_notifications($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT n.*, r.item_name, r.type as report_type 
        FROM notifications n 
        JOIN reports r ON n.report_id = r.id 
        WHERE n.user_id = ? AND n.is_read = false 
        ORDER BY n.created_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get semua notifikasi user (dengan pagination optional)
 */
function get_all_notifications($conn, $user_id, $limit = 20, $offset = 0) {
    $stmt = $conn->prepare("
        SELECT n.*, r.item_name, r.type as report_type 
        FROM notifications n 
        JOIN reports r ON n.report_id = r.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Count notifikasi belum dibaca
 */
function count_unread_notifications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = false");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

/**
 * Mark notifikasi sebagai read
 */
function mark_notification_as_read($conn, $notification_id, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    return $stmt->execute();
}

/**
 * Kirim notifikasi ke users yang cocok dengan laporan baru (menggunakan database, bukan email)
 */
function notify_matching_users_on_new_report_db($conn, $new_report_id, $item_name, $type, $category) {
    $matches = get_matching_reports_for_new_report($conn, $item_name, $new_report_id, $category, $type);
    
    if (empty($matches)) {
        return;
    }

    // Get creator of new report
    $stmt = $conn->prepare("SELECT user_id FROM reports WHERE id = ?");
    $stmt->bind_param("i", $new_report_id);
    $stmt->execute();
    $creator = $stmt->get_result()->fetch_assoc();
    $creator_id = $creator['user_id'];

    $type_label = $type === 'lost' ? 'barang hilang' : 'barang ditemukan';

    foreach ($matches as $match) {
        // Notifikasi ke user yang punya laporan matching
        $message_to_match_owner = "Ada laporan baru: <strong>$item_name</strong> ($type_label) yang mungkin cocok dengan laporan Anda.";
        create_notification($conn, $match['user_id'], $new_report_id, $message_to_match_owner, 'match');

        // Notifikasi ke user yang buat laporan baru
        if ($match['user_id'] != $creator_id) {
            $message_to_creator = "Laporan Anda cocok dengan laporan: <strong>" . $match['item_name'] . "</strong>.";
            create_notification($conn, $creator_id, $match['id'], $message_to_creator, 'match');
        }
    }
}

/**
 * Cek rate limit untuk sebuah aksi
 * @param int $max_attempts Maksimum percobaan dalam jendela waktu
 * @param int $window_minutes Jendela waktu dalam menit
 * @return bool true jika diizinkan, false jika kena limit
 */
function check_rate_limit($conn, $action_type, $max_attempts = 5, $window_minutes = 15) {
    $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cutoff = date('Y-m-d H:i:s', time() - ($window_minutes * 60));

    // Hapus data lama
    $stmt = $conn->prepare("DELETE FROM rate_limits WHERE attempted_at < ?");
    $stmt->bind_param("s", $cutoff);
    $stmt->execute();

    // Hitung percobaan dalam jendela
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM rate_limits WHERE identifier = ? AND action_type = ? AND attempted_at > ?");
    $stmt->bind_param("sss", $identifier, $action_type, $cutoff);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row['cnt'] >= $max_attempts) {
        return false;
    }

    // Catat percobaan
    $stmt = $conn->prepare("INSERT INTO rate_limits (identifier, action_type) VALUES (?, ?)");
    $stmt->bind_param("ss", $identifier, $action_type);
    $stmt->execute();

    return true;
}

/**
 * Catat aksi ke tabel audit_logs
 */
function log_audit($conn, $action, $description = null, $user_id = null) {
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    $username = $_SESSION['username'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $username, $action, $description, $ip, $ua);
    return $stmt->execute();
}

/**
 * Catat akses chat ke tabel chat_access_logs
 */
function log_chat_access($conn, $user_id, $chat_room_id, $action = 'access_chat') {
    // Ambil face_verification_id terakhir user ini
    $stmt = $conn->prepare("SELECT id FROM face_verifications WHERE user_id = ? ORDER BY verified_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $fv = $stmt->get_result()->fetch_assoc();
    $face_verification_id = $fv ? (int)$fv['id'] : null;

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare("INSERT INTO chat_access_logs (user_id, chat_room_id, face_verification_id, ip_address, user_agent, action) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $user_id, $chat_room_id, $face_verification_id, $ip, $ua, $action);
    return $stmt->execute();
}

/**
 * Kirim notifikasi laporan selesai
 */
function notify_report_resolved_db($conn, $report_id) {
    $stmt = $conn->prepare("SELECT user_id, item_name FROM reports WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();

    if (!$report) {
        return false;
    }

    $message = "Laporan Anda untuk <strong>" . $report['item_name'] . "</strong> telah ditandai selesai.";
    return create_notification($conn, $report['user_id'], $report_id, $message, 'resolved');
}
?>

<?php
// includes/functions.php

// Konfigurasi Flask Face Service
define('FLASK_API_URL', 'http://localhost:5000');
define('DEVELOPMENT_MODE', false); // Ubah ke false untuk production

/**
 * Membersihkan input dari karakter berbahaya
 */
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
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
        'Content-Length: ' . strlen($json_data)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
    
    // Catat di face_verifications
    $stmt = $conn->prepare("INSERT INTO face_verifications (user_id, verified_at) VALUES (?, NOW())");
    $stmt->bind_param("i", $user_id);
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
    $result = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE user_id = $user_id AND is_read = false");
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

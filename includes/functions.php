<?php
// includes/functions.php

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
?>

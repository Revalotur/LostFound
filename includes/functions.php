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

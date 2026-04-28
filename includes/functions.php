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
        $class = ($msg['type'] === 'success') ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$class}'>{$msg['text']}</div>";
        unset($_SESSION['flash_message']);
    }
}

/**
 * Fungsi matching sederhana untuk mencari barang yang mirip
 */
function get_matching_reports($conn, $item_name, $current_id) {
    $keywords = explode(' ', $item_name);
    $query_parts = [];
    foreach ($keywords as $word) {
        if (strlen($word) > 2) { // Hanya kata yang lebih dari 2 karakter
            $query_parts[] = "item_name LIKE '%" . $conn->real_escape_string($word) . "%'";
        }
    }

    if (empty($query_parts)) return [];

    $sql = "SELECT * FROM reports WHERE (" . implode(' OR ', $query_parts) . ") AND id != ? AND status = 'open' LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

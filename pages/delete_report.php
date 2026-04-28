<?php
// pages/delete_report.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Get report to check ownership
    $stmt = $conn->prepare("SELECT user_id, image_url FROM reports WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();

    if ($report) {
        // Admin or owner can delete
        if (is_admin() || $_SESSION['user_id'] == $report['user_id']) {
            // Delete image file if exists
            if ($report['image_url']) {
                $filepath = UPLOAD_DIR . $report['image_url'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }

            // Delete from database
            $del_stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
            $del_stmt->bind_param("i", $id);
            if ($del_stmt->execute()) {
                redirect("../index.php", "Laporan berhasil dihapus.", "success");
            }
        }
    }
}

redirect("../index.php", "Gagal menghapus laporan atau Anda tidak memiliki akses.", "danger");
?>

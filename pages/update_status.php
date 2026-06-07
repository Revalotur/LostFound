<?php
// pages/update_status.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect("../auth/login.php", "Silakan login terlebih dahulu.", "danger");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $status = sanitize($_POST['status']);
    $user_id = $_SESSION['user_id'];

    // Check ownership or admin
    $stmt = $conn->prepare("SELECT user_id FROM reports WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();

    if ($report && ($report['user_id'] == $user_id || is_admin())) {
        $update_stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $status, $id);
        
        if ($update_stmt->execute()) {
            if ($status === 'resolved') {
                send_report_resolved_email($conn, $id);
            }
            redirect("detail.php?id=$id", "Status laporan berhasil diperbarui!", "success");
        } else {
            redirect("detail.php?id=$id", "Gagal memperbarui status.", "danger");
        }
    } else {
        redirect("../index.php", "Akses ditolak.", "danger");
    }
} else {
    header("Location: ../index.php");
}
?>
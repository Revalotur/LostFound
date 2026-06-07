<?php
// api/mark_all_read.php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND is_read = false");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}
?>

<?php
// api/mark_notification_read.php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

if (mark_notification_as_read($conn, $notification_id, $user_id)) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
?>

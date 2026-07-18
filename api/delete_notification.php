<?php
// api/delete_notification.php
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

if (!isset($data['csrf_token']) || !verify_csrf($data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit();
}

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notification deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
}
?>

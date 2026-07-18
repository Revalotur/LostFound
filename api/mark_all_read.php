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

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

if (!isset($data['csrf_token']) || !verify_csrf($data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit();
}

$stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND is_read = false");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}
?>

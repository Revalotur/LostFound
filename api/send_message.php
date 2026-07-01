<?php
// api/send_message.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login first'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['room_id']) || !isset($input['message'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Room ID and message are required'
    ]);
    exit;
}

$room_id = intval($input['room_id']);
$sender_id = $_SESSION['user_id'];
$message = sanitize($input['message']);

if (empty(trim($message))) {
    echo json_encode([
        'success' => false,
        'message' => 'Message cannot be empty'
    ]);
    exit;
}

// Cek apakah user adalah anggota dari room ini dan apakah dia initiator
$stmt = $conn->prepare("SELECT * FROM chat_rooms WHERE id = ? AND (initiator_id = ? OR owner_id = ?)");
$stmt->bind_param("iii", $room_id, $sender_id, $sender_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit;
}

// HANYA USER YANG MENJADI INITIATOR YANG HARUS VERIFIKASI
$is_initiator = $room['initiator_id'] === $sender_id;
if ($is_initiator && !is_face_verified($conn)) {
    echo json_encode([
        'success' => false,
        'message' => 'Face verification required',
        'redirect_to' => '../pages/face_verification.php'
    ]);
    exit;
}

// Simpan pesan
$stmt = $conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $room_id, $sender_id, $message);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message_id' => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message'
    ]);
}

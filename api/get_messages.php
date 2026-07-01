<?php
// api/get_messages.php
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

if (!isset($_GET['room_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Room ID is required'
    ]);
    exit;
}

$room_id = intval($_GET['room_id']);
$user_id = $_SESSION['user_id'];

// Cek akses ke room
$stmt = $conn->prepare("
    SELECT cr.*, 
           r.item_name,
           u1.username as initiator_name,
           u2.username as owner_name,
           CASE 
               WHEN cr.initiator_id = ? THEN u2.username
               ELSE u1.username 
           END as chat_partner_name,
           CASE 
               WHEN cr.initiator_id = ? THEN cr.owner_id
               ELSE cr.initiator_id 
           END as chat_partner_id
    FROM chat_rooms cr
    JOIN reports r ON cr.report_id = r.id
    JOIN users u1 ON cr.initiator_id = u1.id
    JOIN users u2 ON cr.owner_id = u2.id
    WHERE cr.id = ? AND (cr.initiator_id = ? OR cr.owner_id = ?)
");
$stmt->bind_param("iiiii", $user_id, $user_id, $room_id, $user_id, $user_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied or room not found'
    ]);
    exit;
}

// Ambil semua pesan
$stmt = $conn->prepare("
    SELECT cm.*, u.username as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    WHERE cm.room_id = ?
    ORDER BY cm.created_at ASC
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tandai pesan dari partner sebagai sudah dibaca
$stmt = $conn->prepare("
    UPDATE chat_messages 
    SET is_read = TRUE 
    WHERE room_id = ? AND sender_id != ? AND is_read = FALSE
");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();

echo json_encode([
    'success' => true,
    'room' => $room,
    'messages' => $messages,
    'current_user_id' => $user_id
]);

<?php
// api/create_room.php
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

// Cek verifikasi wajah
if (!is_face_verified($conn)) {
    echo json_encode([
        'success' => false,
        'message' => 'Face verification required',
        'redirect_to' => '../pages/face_verification.php'
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

if (!isset($input['csrf_token']) || !verify_csrf($input['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Token CSRF tidak valid.'
    ]);
    exit;
}

if (!isset($input['report_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Report ID is required'
    ]);
    exit;
}

$report_id = intval($input['report_id']);
$initiator_id = $_SESSION['user_id'];

// Dapatkan owner_id dari report
$stmt = $conn->prepare("SELECT user_id FROM reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    echo json_encode([
        'success' => false,
        'message' => 'Report not found'
    ]);
    exit;
}

$owner_id = $report['user_id'];

// Tidak bisa chat dengan diri sendiri
if ($initiator_id === $owner_id) {
    echo json_encode([
        'success' => false,
        'message' => 'You cannot chat with yourself'
    ]);
    exit;
}

// Cek apakah room sudah ada
$stmt = $conn->prepare("SELECT id FROM chat_rooms WHERE report_id = ? AND ((initiator_id = ? AND owner_id = ?) OR (initiator_id = ? AND owner_id = ?))");
$stmt->bind_param("iiiii", $report_id, $initiator_id, $owner_id, $owner_id, $initiator_id);
$stmt->execute();
$existing_room = $stmt->get_result()->fetch_assoc();

if ($existing_room) {
    echo json_encode([
        'success' => true,
        'room_id' => $existing_room['id']
    ]);
    exit;
}

// Buat room baru
$stmt = $conn->prepare("INSERT INTO chat_rooms (report_id, initiator_id, owner_id) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $report_id, $initiator_id, $owner_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'room_id' => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create chat room'
    ]);
}

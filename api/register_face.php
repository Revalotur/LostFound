<?php
// api/register_face.php
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

if (!isset($input['csrf_token']) || !verify_csrf($input['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Token CSRF tidak valid.'
    ]);
    exit;
}

if (!isset($input['image'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Image is required'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$image_base64 = $input['image'];

$result = register_face($conn, $user_id, $image_base64);

echo json_encode($result);

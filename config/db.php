<?php
// config/db.php

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'lost_and_found_db';

// Menggunakan mysqli dengan mode exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Global path constants — auto-detect BASE_URL
$base_url = getenv('BASE_URL');
if (!$base_url) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $config_path = __DIR__;
    if ($doc_root && strpos($config_path, $doc_root) === 0) {
        $rel = str_replace('\\', '/', substr($config_path, strlen($doc_root)));
        $base_path = dirname($rel);
        $base_url = "$scheme://$host$base_path/";
    } else {
        $base_url = 'http://localhost/LostFound/';
    }
}
define('BASE_URL', $base_url);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
?>

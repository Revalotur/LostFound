<?php
// config/db.php

$host = 'localhost';
$user = 'fabio';
$pass = '123456';
$db   = 'lost_and_found_db';

// Menggunakan mysqli dengan mode exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Global path constants
define('BASE_URL', 'http://localhost/BarangHilang/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
?>

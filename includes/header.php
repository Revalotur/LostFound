<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found - Temukan Barang Anda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="<?php echo BASE_URL; ?>" class="logo">LostFound</a>
                
                <button class="mobile-toggle" aria-label="Toggle navigation">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>

                <div class="nav-links">
                    <a href="<?php echo BASE_URL; ?>">Beranda</a>
                    <?php if (is_logged_in()): ?>
                        <div class="user-dropdown">
                            <button class="dropdown-toggle">
                                👤 <?php echo $_SESSION['username']; ?>
                                <span class="arrow"></span>
                            </button>
                            <div class="dropdown-menu">
                                <a href="<?php echo BASE_URL; ?>pages/profile.php">Profil Saya</a>
                                <?php if (is_admin()): ?>
                                    <a href="<?php echo BASE_URL; ?>pages/admin.php">Admin Panel</a>
                                <?php endif; ?>
                                <hr>
                                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="logout-link">Logout</a>
                            </div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>pages/add_report.php" class="btn btn-primary">Buat Laporan</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
                        <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary">Daftar Sekarang</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php display_flash_message(); ?>

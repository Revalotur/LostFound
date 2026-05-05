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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=1.1">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Leaflet Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Leaflet Control Geocoder -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <div class="logo-icon">
                        <i data-lucide="search"></i>
                    </div>
                    LostFound
                </a>
                
                <div class="nav-links">
                    <a href="<?php echo BASE_URL; ?>" class="nav-link">Beranda</a>
                    
                    <button id="theme-toggle" class="nav-link" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; color: var(--text-light);">
                        <i data-lucide="moon" class="dark-icon"></i>
                        <i data-lucide="sun" class="light-icon" style="display: none;"></i>
                    </button>

                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <a href="<?php echo BASE_URL; ?>pages/admin.php" class="nav-link">Admin Panel</a>
                        <?php endif; ?>
                        <div class="user-info" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--text);">
                            <i data-lucide="user" style="width: 18px;"></i>
                            <?php echo $_SESSION['username']; ?>
                        </div>
                        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-danger" style="padding: 0.5rem 1rem;">Keluar</a>
                        <a href="<?php echo BASE_URL; ?>pages/add_report.php" class="btn btn-primary">
                            <i data-lucide="plus-circle" style="width: 18px;"></i>
                            Buat Laporan
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>auth/login.php" class="nav-link">Masuk</a>
                        <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary">Daftar Sekarang</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php display_flash_message(); ?>

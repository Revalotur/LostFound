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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=1.3">
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
                        <img src="<?php echo BASE_URL; ?>assets/images/LogoNew.png" alt="LostFound" class="logo-img">
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
                        <!-- Notification Bell -->
                        <div style="position: relative;">
                            <button id="notif-bell" class="nav-link" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; color: var(--text-light); position: relative;">
                                <i data-lucide="bell" style="width: 20px;"></i>
                                <?php 
                                    $unread_count = count_unread_notifications($conn, $_SESSION['user_id']);
                                    if ($unread_count > 0):
                                ?>
                                <span style="position: absolute; top: -8px; right: -8px; background: var(--danger); color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                    <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                                </span>
                                <?php endif; ?>
                            </button>
                            
                            <!-- Notification Dropdown -->
                            <div id="notif-dropdown" style="display: none; position: absolute; top: 100%; right: 0; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); width: 320px; max-height: 400px; overflow-y: auto; z-index: 1000; margin-top: 0.5rem;">
                                <?php 
                                    $unread_notifs = get_unread_notifications($conn, $_SESSION['user_id']);
                                    if (!empty($unread_notifs)):
                                        foreach ($unread_notifs as $notif):
                                ?>
                                <div class="notif-item" data-notif-id="<?php echo $notif['id']; ?>" style="padding: 1rem; border-bottom: 1px solid var(--border); cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'" onclick="markNotificationAsRead(<?php echo $notif['id']; ?>, <?php echo $notif['report_id']; ?>)">
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <?php 
                                            echo $notif['type'] === 'match' ? '🔔 Match' : '✓ Selesai';
                                        ?>
                                    </div>
                                    <div style="color: var(--text); margin-top: 0.3rem; font-size: 0.9rem;">
                                        <?php echo $notif['message']; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 0.3rem;">
                                        <?php echo time_ago($notif['created_at']); ?>
                                    </div>
                                </div>
                                <?php 
                                        endforeach;
                                    else:
                                ?>
                                <div style="padding: 1.5rem; text-align: center; color: var(--text-light);">
                                    Tidak ada notifikasi
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($unread_notifs)): ?>
                                <div style="padding: 0.75rem; text-align: center; border-top: 1px solid var(--border);">
                                    <a href="<?php echo BASE_URL; ?>pages/notifications.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                        Lihat Semua Notifikasi →
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (is_admin()): ?>
                            <a href="<?php echo BASE_URL; ?>pages/admin.php" class="nav-link">Admin Panel</a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>pages/profile.php" class="user-info" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--text); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text)'">
                            <i data-lucide="user" style="width: 18px;"></i>
                            <?php echo $_SESSION['username']; ?>
                        </a>
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

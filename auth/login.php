<?php
// auth/login.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF tidak valid.";
    } elseif (!check_rate_limit($conn, 'login', 5, 15)) {
        $error = "Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.";
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            log_audit($conn, 'login', 'User logged in', $user['id']);
            redirect("../index.php", "Selamat datang kembali, " . $user['username'] . "!", "success");
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LostFound</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=1.3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-page">
    <div class="auth-card animate-fade-in">
        <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
            <div class="logo-icon">
                <img src="<?php echo BASE_URL; ?>assets/images/LogoNew.png" alt="LostFound" class="logo-img">
            </div>
            LostFound
        </div>
        
        <h2>Selamat Datang Kembali</h2>
        <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem;">Silakan masuk ke akun Anda untuk melanjutkan.</p>
        
        <?php display_flash_message(); ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Username</label>
                <div style="position: relative;">
                    <i data-lucide="user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-light);"></i>
                    <input type="text" name="username" required placeholder="Masukkan username" style="padding-left: 3rem;">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div style="position: relative;">
                    <i data-lucide="lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-light);"></i>
                    <input type="password" name="password" required placeholder="Masukkan password" style="padding-left: 3rem;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                Masuk Sekarang
            </button>
        </form>
        
        <div style="margin-top: 2rem; text-align: center; color: var(--text-light); font-size: 0.875rem;">
            Belum punya akun? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Daftar di sini</a>
        </div>
    </div>
    
    <footer style="margin-top: 2rem; text-align: center; color: var(--text-light); font-size: 0.875rem; padding-bottom: 2rem;">
        <p>&copy; 2026 Lost & Found System. Developed by <strong>FabioGanteng</strong></p>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

<?php
// auth/register.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Password tidak cocok!";
    } else {
        // Cek apakah username/email sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username atau Email sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                redirect("login.php", "Registrasi berhasil! Silakan login.", "success");
            } else {
                $error = "Terjadi kesalahan saat registrasi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - LostFound</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=1.2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-page">
    <div class="auth-card animate-fade-in">
        <div class="logo" style="justify-content: center; margin-bottom: 1.5rem;">
            <div class="logo-icon">
                <i data-lucide="search"></i>
            </div>
            LostFound
        </div>
        
        <h2 style="margin-bottom: 0.5rem;">Daftar Akun Baru</h2>
        <p style="text-align: center; color: var(--text-light); margin-bottom: 1.5rem;">Bergabunglah dengan komunitas kami sekarang.</p>
        
        <?php display_flash_message(); ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <div style="position: relative;">
                    <i data-lucide="user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-light);"></i>
                    <input type="text" name="username" required placeholder="Masukkan username" style="padding-left: 3rem;">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div style="position: relative;">
                    <i data-lucide="mail" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-light);"></i>
                    <input type="email" name="email" required placeholder="Masukkan email" style="padding-left: 3rem;">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div style="position: relative;">
                    <i data-lucide="lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-light);"></i>
                    <input type="password" name="password" required placeholder="Masukkan password" style="padding-left: 3rem;">
                </div>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div style="position: relative;">
                    <i data-lucide="check-circle" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-light);"></i>
                    <input type="password" name="confirm_password" required placeholder="Ulangi password" style="padding-left: 3rem;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                Daftar Sekarang
            </button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; color: var(--text-light); font-size: 0.875rem;">
            Sudah punya akun? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Masuk di sini</a>
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

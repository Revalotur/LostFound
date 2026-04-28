<?php
// pages/profile.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect("../auth/login.php", "Silakan login terlebih dahulu untuk mengakses profil.", "danger");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil data user saat ini
$stmt = $conn->prepare("SELECT username, email, contact FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = sanitize($_POST['email']);
    $contact = sanitize($_POST['contact']);
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Update profile
        $update_stmt = $conn->prepare("UPDATE users SET email = ?, contact = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $email, $contact, $user_id);
        
        if ($update_stmt->execute()) {
            redirect("profile.php", "Profil berhasil diperbarui!", "success");
        } else {
            $error = "Gagal memperbarui profil.";
        }
    }
}

include '../includes/header.php';
?>

<div style="max-width: 600px; margin: 2rem auto;">
    <div class="auth-card" style="max-width: 100%;">
        <h2>Edit Profil</h2>
        <p>Perbarui informasi kontak Anda agar orang lain dapat menghubungi Anda.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo $user['username']; ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                <small style="color: var(--text-light);">Username tidak dapat diubah.</small>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required placeholder="Masukkan email">
            </div>

            <div class="form-group">
                <label>Kontak Lain (WhatsApp / No. HP / Sosmed)</label>
                <input type="text" name="contact" value="<?php echo $user['contact']; ?>" placeholder="Contoh: WA: 08123456789 atau IG: @username">
                <small style="color: var(--text-light);">Informasi ini akan ditampilkan di laporan yang Anda buat.</small>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <a href="../index.php" class="btn" style="background: var(--border); flex: 1; text-align: center;">Kembali</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

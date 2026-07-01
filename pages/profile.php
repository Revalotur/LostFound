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
$stmt = $conn->prepare("SELECT username, email, contact, face_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek apakah ini form update profil atau ubah password
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        // Ubah Password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validasi
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Semua kolom password harus diisi.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok.";
        } else {
            // Cek password lama
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $db_user = $result->fetch_assoc();

            if (password_verify($current_password, $db_user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password berhasil diperbarui!";
                } else {
                    $error = "Gagal memperbarui password.";
                }
            } else {
                $error = "Password lama salah.";
            }
        }
    } else {
        // Update Profil
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
                $success = "Profil berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui profil.";
            }
        }
    }
}

include '../includes/header.php';
?>

<div style="max-width: 600px; margin: 2rem auto;">
    <div class="auth-card" style="max-width: 100%;">
        <h2>Edit Profil</h2>
        <p>Perbarui informasi kontak dan pengaturan akun Anda.</p>
        
        <!-- Status Verifikasi -->
        <div style="background: var(--bg); padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <?php if ($user['face_verified']): ?>
                    <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="check-circle-2" style="color: var(--success); width: 22px; height: 22px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: var(--text);">Identity Verified</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Akun Anda telah terverifikasi, chat bisa langsung diakses!</div>
                    </div>
                <?php else: ?>
                    <div style="width: 40px; height: 40px; background: rgba(100, 116, 139, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="circle" style="color: var(--text-light); width: 22px; height: 22px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: var(--text);">Not Verified</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Verifikasi wajah untuk menggunakan fitur chat</div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$user['face_verified']): ?>
                <a href="face_verification.php?redirect_to=profile.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">
                    <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                    Verifikasi
                </a>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Form Update Profil -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.1rem; color: var(--text);">Informasi Profil</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo $user['username']; ?>" readonly style="background: var(--surface); color: var(--text); cursor: default;">
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

            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <a href="../index.php" class="btn" style="background: var(--border); flex: 1; text-align: center;">Kembali</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Simpan Perubahan</button>
            </div>
        </form>

        <!-- Form Ubah Password -->
        <hr style="border: none; border-top: 1px solid var(--border); margin: 2.5rem 0;">
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: var(--text);">Ubah Password</h3>
        <form action="" method="POST">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label>Password Lama</label>
                <input type="password" name="current_password" required placeholder="Masukkan password lama">
            </div>

            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" required placeholder="Masukkan password baru (min. 6 karakter)">
            </div>

            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" required placeholder="Masukkan kembali password baru">
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Ubah Password</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
// pages/add_report.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect("../auth/login.php", "Silakan login terlebih dahulu untuk membuat laporan.", "danger");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF tidak valid.";
    } else {
        $type        = sanitize($_POST['type']);
        $item_name   = sanitize($_POST['item_name']);
        $category    = sanitize($_POST['category']);
        $description = sanitize($_POST['description']);
        $location    = sanitize($_POST['location']);
        $latitude    = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude   = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        $event_date  = $_POST['event_date'];
        $user_id     = $_SESSION['user_id'];

    // Handle File Upload
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$upload_ok, $upload_result] = validate_upload($_FILES['image']);
        if ($upload_ok) {
            $image_url = $upload_result;
        } else {
            $error = $upload_result;
        }
    }

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO reports (user_id, type, item_name, category, description, location, latitude, longitude, event_date, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssddss", $user_id, $type, $item_name, $category, $description, $location, $latitude, $longitude, $event_date, $image_url);

            if ($stmt->execute()) {
                $new_report_id = $conn->insert_id;
                log_audit($conn, 'create_report', "Created report #$new_report_id: $item_name ($type)");
                notify_matching_users_on_new_report_db($conn, $new_report_id, $item_name, $type, $category);
                redirect("../index.php", "Laporan berhasil dibuat! Notifikasi akan dikirim ke user yang cocok.", "success");
            } else {
                $error = "Gagal menyimpan laporan: " . $stmt->error;
            }
        }
    }
}

include '../includes/header.php';
?>

<div style="max-width: 800px; margin: 2rem auto;">
    <div class="auth-card" style="max-width: 100%;">
        <h2>Buat Laporan Baru</h2>
        <p>Berikan detail yang jelas untuk memudahkan pencarian.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Jenis Laporan</label>
                <select name="type" required>
                    <option value="lost">Saya Kehilangan Barang (Hilang)</option>
                    <option value="found">Saya Menemukan Barang (Ditemukan)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Nama Barang</label>
                <input type="text" name="item_name" required placeholder="Contoh: Dompet Kulit Cokelat">
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <select name="category" required>
                    <option value="Elektronik">Elektronik</option>
                    <option value="Dokumen/Surat">Dokumen/Surat</option>
                    <option value="Aksesoris">Aksesoris</option>
                    <option value="Pakaian">Pakaian</option>
                    <option value="Hewan Peliharaan">Hewan Peliharaan</option>
                    <option value="Kunci">Kunci</option>
                    <option value="Lainnya" selected>Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label>Lokasi Kejadian (Nama Tempat)</label>
                <input type="text" name="location" id="location" required placeholder="Ketik nama tempat, misal 'Stasiun Gambir' atau 'Kantin Gedung A'">
                <small style="color: var(--text-light);">Mulai ketik untuk mencari lokasi otomatis, lalu pilih saran. Atau tandai langsung di peta di bawah.</small>
            </div>

            <div class="form-group">
                <label>Pilih Lokasi di Peta (Opsional)</label>
                <div id="map-picker" style="height: 300px; border-radius: var(--radius); margin-bottom: 0.5rem; border: 1px solid var(--border);"></div>
                <input type="hidden" name="latitude" id="lat">
                <input type="hidden" name="longitude" id="lng">
            </div>

            <div class="form-group">
                <label>Tanggal Kejadian</label>
                <input type="date" name="event_date" required>
            </div>

            <div class="form-group">
                <label>Deskripsi Detail</label>
                <textarea name="description" rows="4" required placeholder="Sebutkan ciri-ciri khusus barang..."></textarea>
            </div>

            <div class="form-group">
                <label>Foto Barang (Opsional)</label>
                <input type="file" name="image" accept="image/*">
                <small style="color: var(--text-light);">Format: JPG, PNG, WEBP. Maks 2MB.</small>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <a href="../index.php" class="btn" style="background: var(--border); flex: 1; text-align: center;">Batal</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>assets/js/location-picker.js"></script>
<script>
    initLocationPicker({
        mapElId: 'map-picker',
        searchInputEl: document.getElementById('location'),
        latInputId: 'lat',
        lngInputId: 'lng',
        defaultLat: -6.200000,
        defaultLng: 106.816666,
        defaultZoom: 13
    });
</script>

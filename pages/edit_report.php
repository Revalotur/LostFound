<?php
// pages/edit_report.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Harus login
if (!is_logged_in()) {
    redirect("../auth/login.php", "Silakan login terlebih dahulu.", "danger");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect("../index.php");
}

// Ambil data laporan
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    redirect("../index.php", "Laporan tidak ditemukan.", "danger");
}

// Hanya pemilik atau admin yang boleh edit
if ($report['user_id'] !== $_SESSION['user_id'] && !is_admin()) {
    redirect("../index.php", "Anda tidak memiliki izin untuk mengedit laporan ini.", "danger");
}

$error = '';

// Proses form edit
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

        // Gambar: tetap pakai yang lama jika tidak ada upload baru
        $image_url = $report['image_url'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            [$upload_ok, $upload_result] = validate_upload($_FILES['image']);
            if ($upload_ok) {
                // Hapus foto lama jika ada
                if ($report['image_url'] && file_exists(UPLOAD_DIR . $report['image_url'])) {
                    unlink(UPLOAD_DIR . $report['image_url']);
                }
                $image_url = $upload_result;
            } else {
                $error = $upload_result;
            }
        }

        // Hapus foto jika user centang "Hapus Foto"
        if (isset($_POST['delete_image']) && $_POST['delete_image'] === '1') {
            if ($report['image_url'] && file_exists(UPLOAD_DIR . $report['image_url'])) {
                unlink(UPLOAD_DIR . $report['image_url']);
            }
            $image_url = null;
        }

        if (!$error) {
            $stmt = $conn->prepare("
                UPDATE reports 
                SET type=?, item_name=?, category=?, description=?, location=?, 
                    latitude=?, longitude=?, event_date=?, image_url=?
                WHERE id=?
            ");
            $stmt->bind_param('sssssssssi', $type, $item_name, $category, $description, $location, $latitude, $longitude, $event_date, $image_url, $id);

            if ($stmt->execute()) {
                log_audit($conn, 'edit_report', "Edited report #$id: $item_name");
                redirect("detail.php?id=$id", "Laporan berhasil diperbarui!", "success");
            } else {
                $error = "Gagal memperbarui laporan: " . $stmt->error;
            }
        }
    }
}

include '../includes/header.php';
?>

<div style="max-width: 800px; margin: 2rem auto;">
    <div class="auth-card" style="max-width: 100%;">
        <h2>Edit Laporan</h2>
        <p>Perbarui informasi laporan barang Anda.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <!-- Jenis Laporan -->
            <div class="form-group">
                <label>Jenis Laporan</label>
                <select name="type" required>
                    <option value="lost"  <?php echo $report['type'] === 'lost'  ? 'selected' : ''; ?>>Saya Kehilangan Barang (Hilang)</option>
                    <option value="found" <?php echo $report['type'] === 'found' ? 'selected' : ''; ?>>Saya Menemukan Barang (Ditemukan)</option>
                </select>
            </div>

            <!-- Nama Barang -->
            <div class="form-group">
                <label>Nama Barang</label>
                <input type="text" name="item_name" required
                       value="<?php echo htmlspecialchars($report['item_name']); ?>"
                       placeholder="Contoh: Dompet Kulit Cokelat">
            </div>

            <!-- Kategori -->
            <div class="form-group">
                <label>Kategori</label>
                <select name="category" required>
                    <?php
                    $categories = ['Elektronik','Dokumen/Surat','Aksesoris','Pakaian','Hewan Peliharaan','Kunci','Lainnya'];
                    foreach ($categories as $cat):
                    ?>
                    <option value="<?php echo $cat; ?>" <?php echo $report['category'] === $cat ? 'selected' : ''; ?>>
                        <?php echo $cat; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lokasi Teks -->
            <div class="form-group">
                <label>Lokasi Kejadian (Nama Tempat)</label>
                <input type="text" name="location" id="location" required
                       value="<?php echo htmlspecialchars($report['location']); ?>"
                       placeholder="Ketik nama tempat, misal 'Stasiun Gambir'">
                <small style="color: var(--text-light);">Mulai ketik untuk mencari lokasi otomatis, lalu pilih saran. Atau tandai langsung di peta di bawah.</small>
            </div>

            <!-- Peta -->
            <div class="form-group">
                <label>Pilih Lokasi di Peta (Opsional)</label>
                <div id="map-picker" style="height: 300px; border-radius: var(--radius); margin-bottom: 0.5rem; border: 1px solid var(--border);"></div>
                <input type="hidden" name="latitude"  id="lat" value="<?php echo $report['latitude']; ?>">
                <input type="hidden" name="longitude" id="lng" value="<?php echo $report['longitude']; ?>">
            </div>

            <!-- Tanggal -->
            <div class="form-group">
                <label>Tanggal Kejadian</label>
                <input type="date" name="event_date" required
                       value="<?php echo e($report['event_date']); ?>">
            </div>

            <!-- Deskripsi -->
            <div class="form-group">
                <label>Deskripsi Detail</label>
                <textarea name="description" rows="4" required
                          placeholder="Sebutkan ciri-ciri khusus barang..."><?php echo htmlspecialchars($report['description']); ?></textarea>
            </div>

            <!-- Foto -->
            <div class="form-group">
                <label>Foto Barang</label>

                <?php if ($report['image_url']): ?>
                <div style="margin-bottom: 1rem;">
                    <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.5rem;">Foto saat ini:</p>
                    <img src="../uploads/<?php echo e($report['image_url']); ?>"
                         alt="Foto barang"
                         style="max-width: 200px; border-radius: var(--radius); border: 1px solid var(--border);">
                    <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="delete_image" id="delete_image" value="1">
                        <label for="delete_image" style="font-size: 0.875rem; color: #ef4444; cursor: pointer; margin: 0;">
                            Hapus foto ini
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <input type="file" name="image" accept="image/*">
                <small style="color: var(--text-light);">Upload foto baru untuk mengganti yang lama. Format: JPG, PNG, WEBP.</small>
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <a href="detail.php?id=<?php echo $id; ?>" class="btn"
                   style="background: var(--border); flex: 1; text-align: center;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>assets/js/location-picker.js"></script>
<script>
    var initLat = <?php echo !empty($report['latitude'])  ? $report['latitude']  : '-6.200000'; ?>;
    var initLng = <?php echo !empty($report['longitude']) ? $report['longitude'] : '106.816666'; ?>;
    var hasCoord = <?php echo (!empty($report['latitude']) && !empty($report['longitude'])) ? 'true' : 'false'; ?>;

    initLocationPicker({
        mapElId: 'map-picker',
        searchInputEl: document.getElementById('location'),
        latInputId: 'lat',
        lngInputId: 'lng',
        defaultLat: initLat,
        defaultLng: initLng,
        defaultZoom: hasCoord ? 15 : 13
    });
</script>
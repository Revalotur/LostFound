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
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('IMG_', true) . '.' . $ext;
            $destination = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_url = $new_filename;
            } else {
                $error = "Gagal mengupload gambar.";
            }
        } else {
            $error = "Format file tidak didukung (Gunakan: JPG, PNG, WEBP).";
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO reports (user_id, type, item_name, category, description, location, latitude, longitude, event_date, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssddss", $user_id, $type, $item_name, $category, $description, $location, $latitude, $longitude, $event_date, $image_url);

        if ($stmt->execute()) {
            redirect("../index.php", "Laporan berhasil dibuat!", "success");
        } else {
            $error = "Gagal menyimpan laporan: " . $stmt->error;
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
                <input type="text" name="location" required placeholder="Contoh: Kantin Gedung A atau Jl. Merdeka">
            </div>

            <div class="form-group">
                <label>Pilih Lokasi di Peta (Opsional)</label>
                <div id="map-picker" style="height: 300px; border-radius: var(--radius); margin-bottom: 1rem; border: 1px solid var(--border);"></div>
                <input type="hidden" name="latitude" id="lat">
                <input type="hidden" name="longitude" id="lng">
                <small style="color: var(--text-light);">Klik pada peta untuk menandai lokasi kejadian.</small>
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

<script>
    // Initialize Map Picker
    var map = L.map('map-picker').setView([-6.200000, 106.816666], 13); // Default to Jakarta
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var marker;

    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }

        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
    });

    // Try to get user location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            map.setView([lat, lng], 13);
        });
    }
</script>

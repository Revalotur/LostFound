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

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('IMG_', true) . '.' . $ext;
            $destination  = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // Hapus foto lama jika ada
                if ($report['image_url'] && file_exists(UPLOAD_DIR . $report['image_url'])) {
                    unlink(UPLOAD_DIR . $report['image_url']);
                }
                $image_url = $new_filename;
            } else {
                $error = "Gagal mengupload gambar.";
            }
        } else {
            $error = "Format file tidak didukung (Gunakan: JPG, PNG, WEBP).";
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
            redirect("detail.php?id=$id", "Laporan berhasil diperbarui!", "success");
        } else {
            $error = "Gagal memperbarui laporan: " . $stmt->error;
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
                <input type="text" name="location" required
                       value="<?php echo htmlspecialchars($report['location']); ?>"
                       placeholder="Contoh: Kantin Gedung A">
            </div>

            <!-- Peta -->
            <div class="form-group">
                <label>Pilih Lokasi di Peta (Opsional)</label>
                <input type="text" id="location-search" placeholder="Cari lokasi...">
                <ul id="location-suggestions" class="location-suggestions"></ul>
                <div id="map-picker" style="height: 300px; border-radius: var(--radius); margin-bottom: 1rem; border: 1px solid var(--border);"></div>
                <input type="hidden" name="latitude"  id="lat" value="<?php echo $report['latitude']; ?>">
                <input type="hidden" name="longitude" id="lng" value="<?php echo $report['longitude']; ?>">
                <small style="color: var(--text-light);">Klik peta atau cari lokasi untuk memperbarui pin.</small>
            </div>

            <!-- Tanggal -->
            <div class="form-group">
                <label>Tanggal Kejadian</label>
                <input type="date" name="event_date" required
                       value="<?php echo $report['event_date']; ?>">
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
                    <img src="../uploads/<?php echo $report['image_url']; ?>"
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

<script>
    // Koordinat awal: pakai data laporan jika ada, fallback Jakarta
    var initLat = <?php echo !empty($report['latitude'])  ? $report['latitude']  : '-6.200000'; ?>;
    var initLng = <?php echo !empty($report['longitude']) ? $report['longitude'] : '106.816666'; ?>;
    var initZoom = <?php echo !empty($report['latitude']) ? '15' : '13'; ?>;

    var map = L.map('map-picker').setView([initLat, initLng], initZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    map.invalidateSize();

    // Tampilkan marker awal jika ada koordinat
    var marker;
    <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
    marker = L.marker([initLat, initLng]).addTo(map);
    <?php endif; ?>

    var searchInput      = document.getElementById('location-search');
    var suggestionList   = document.getElementById('location-suggestions');
    var locationInput    = document.querySelector('input[name="location"]');

    function debounce(func, wait) {
        var timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() { func.apply(this, arguments); }, wait);
        };
    }

    function clearSuggestions() {
        suggestionList.innerHTML = '';
        suggestionList.style.display = 'none';
    }

    function selectLocation(result) {
        var lat = parseFloat(result.lat);
        var lng = parseFloat(result.lon);
        map.setView([lat, lng], 16);
        if (marker) { marker.setLatLng([lat, lng]); }
        else { marker = L.marker([lat, lng]).addTo(map); }
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
        if (locationInput) { locationInput.value = result.display_name; }
        clearSuggestions();
    }

    function showSuggestions(results) {
        suggestionList.innerHTML = '';
        if (!results || !results.length) { clearSuggestions(); return; }
        results.forEach(function(result) {
            var item = document.createElement('li');
            item.textContent = result.display_name;
            item.addEventListener('click', function() { selectLocation(result); });
            suggestionList.appendChild(item);
        });
        suggestionList.style.display = 'block';
    }

    searchInput.addEventListener('input', debounce(function(e) {
        var q = e.target.value;
        if (!q || q.length < 3) { clearSuggestions(); return; }
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&accept-language=id&q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(showSuggestions)
            .catch(clearSuggestions);
    }, 300));

    document.addEventListener('click', function(e) {
        if (!suggestionList.contains(e.target) && e.target !== searchInput) { clearSuggestions(); }
    });

    map.on('click', function(e) {
        if (marker) { marker.setLatLng(e.latlng); }
        else { marker = L.marker(e.latlng).addTo(map); }
        document.getElementById('lat').value = e.latlng.lat;
        document.getElementById('lng').value = e.latlng.lng;
    });
</script>
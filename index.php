<?php
// index.php
include 'includes/header.php';

// Search & Filter Logic
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type   = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$status   = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "SELECT r.*, u.username, u.face_verified FROM reports r JOIN users u ON r.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND r.item_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($type) {
    $sql .= " AND r.type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($location) {
    $sql .= " AND r.location = ?";
    $params[] = $location;
    $types .= "s";
}

if ($category) {
    $sql .= " AND r.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($status) {
    $sql .= " AND r.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();

// Get unique locations for filter
$loc_stmt = $conn->query("SELECT DISTINCT location FROM reports ORDER BY location ASC");
$locations = $loc_stmt->fetch_all(MYSQLI_ASSOC);

// Ambil koordinat untuk peta (hanya laporan yang punya lat/long)
$map_stmt = $conn->prepare("
    SELECT r.id, r.item_name, r.type, r.latitude, r.longitude, r.location
    FROM reports r
    WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL AND r.latitude != '' AND r.longitude != ''
");
$map_stmt->execute();
$map_points = $map_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$map_json = json_encode($map_points);
?>

<div class="hero animate-fade-in">
    <h1>Temukan Barang <br><span style="color: var(--primary);">Yang Hilang</span> Kembali</h1>
    <p>Platform komunitas modern untuk saling membantu menemukan barang berharga Anda dengan cepat dan mudah.</p>
    
    <div class="search-container">
        <form action="" method="GET" class="filters">
            <div class="filter-group">
                <i data-lucide="search" style="width: 18px;"></i>
                <input type="text" name="search" value="<?php echo $search; ?>" class="filter-input" placeholder="Apa yang Anda cari?">
            </div>
            <div class="filter-group">
                <i data-lucide="tag" style="width: 18px;"></i>
                <select name="type" class="filter-input">
                    <option value="">Semua Jenis</option>
                    <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Barang Hilang</option>
                    <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Barang Ditemukan</option>
                </select>
            </div>
            <div class="filter-group">
                <i data-lucide="layers" style="width: 18px;"></i>
                <select name="category" class="filter-input">
                    <option value="">Semua Kategori</option>
                    <option value="Elektronik" <?php echo $category === 'Elektronik' ? 'selected' : ''; ?>>Elektronik</option>
                    <option value="Dokumen/Surat" <?php echo $category === 'Dokumen/Surat' ? 'selected' : ''; ?>>Dokumen/Surat</option>
                    <option value="Aksesoris" <?php echo $category === 'Aksesoris' ? 'selected' : ''; ?>>Aksesoris</option>
                    <option value="Pakaian" <?php echo $category === 'Pakaian' ? 'selected' : ''; ?>>Pakaian</option>
                    <option value="Hewan Peliharaan" <?php echo $category === 'Hewan Peliharaan' ? 'selected' : ''; ?>>Hewan Peliharaan</option>
                    <option value="Kunci" <?php echo $category === 'Kunci' ? 'selected' : ''; ?>>Kunci</option>
                    <option value="Lainnya" <?php echo $category === 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                </select>
            </div>
            <div class="filter-group">
                <i data-lucide="circle-dot" style="width: 18px;"></i>
                <select name="status" class="filter-input">
                    <option value="">Semua Status</option>
                    <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Terbuka</option>
                    <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
            <div class="filter-group filter-location">
                <i data-lucide="map-pin" style="width: 18px;"></i>
                <select name="location" class="filter-input">
                    <option value="">Semua Lokasi</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo e($loc['location']); ?>" <?php echo $location === $loc['location'] ? 'selected' : ''; ?>>
                            <?php echo e($loc['location']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-search">
                Cari Barang
            </button>
        </form>
    </div>
</div>

<div class="map-section animate-fade-in" style="margin: 2rem 0; background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); padding: 1.25rem; box-shadow: var(--shadow);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 0.5rem; margin: 0;">
            <i data-lucide="map" style="width: 18px;"></i>
            Peta Lokasi Laporan
            <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-light);">(<?php echo count($map_points); ?> titik)</span>
        </h3>
        <button type="button" id="toggle-map" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--bg);">
            <i data-lucide="eye-off" style="width: 14px;"></i> Sembunyikan
        </button>
    </div>
    <div id="map-index" style="height: 380px; border-radius: var(--radius); border: 1px solid var(--border);"></div>
</div>

<div class="report-grid animate-fade-in" style="animation-delay: 0.2s;">
    <?php if ($reports->num_rows > 0): ?>
        <?php while($row = $reports->fetch_assoc()): ?>
            <div class="report-card">
                <div class="card-image">
                    <span class="type-badge <?php echo $row['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                        <?php echo $row['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                    </span>
                    <?php if ($row['status'] === 'resolved'): ?>
                        <span class="type-badge" style="left: auto; right: 1rem; background: #10b981;">Selesai</span>
                    <?php endif; ?>
                    <?php if ($row['image_url']): ?>
                    <img src="uploads/<?php echo e($row['image_url']); ?>" alt="<?php echo e($row['item_name']); ?>">
<?php else: ?>
    <div style="width: 100%; height: 100%; background: var(--bg); display: flex; align-items: center; justify-content: center; color: var(--text-light);">
        <i data-lucide="image-off" style="width: 48px; height: 48px;"></i>
    </div>
<?php endif; ?>
                </div>
                
                <div class="card-body">
                    <div class="card-title-row">
                        <h3 class="card-title"><?php echo e($row['item_name']); ?></h3>
                        <span class="category-badge">
                            <?php echo e($row['category'] ?? 'Lainnya'); ?>
                        </span>
                    </div>
                    <div class="card-info">
                        <div class="info-row">
                            <i data-lucide="map-pin"></i>
                            <span><?php echo e($row['location']); ?></span>
                        </div>
                        <div class="info-row">
                            <i data-lucide="calendar"></i>
                            <span><?php echo time_ago($row['created_at']); ?></span>
                        </div>
                        <div class="info-row">
                            <i data-lucide="user"></i>
                            <span style="display: flex; align-items: center; gap: 6px;">
                                Oleh: <?php echo e($row['username']); ?>
                                <?php if ($row['face_verified']): ?>
                                    <i data-lucide="check-circle-2" style="width: 14px; height: 14px; color: var(--success);"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <a href="pages/detail.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-primary btn-block">
                        Lihat Detail
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 5rem; background: var(--surface); border-radius: var(--radius); border: 2px dashed var(--border);">
            <i data-lucide="package-search" style="width: 64px; height: 64px; color: var(--text-light); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text);">Tidak ada laporan ditemukan</h3>
            <p style="color: var(--text-light);">Coba ubah kata kunci atau filter pencarian Anda.</p>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($map_points)): ?>
<script>
    var mapPoints = <?php echo $map_json; ?>;

    function initIndexMap() {
        var map = L.map('map-index');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var bounds = [];
        mapPoints.forEach(function(p) {
            var lat = parseFloat(p.latitude);
            var lng = parseFloat(p.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            var marker = L.marker([lat, lng]).addTo(map);
            var icon = p.type === 'lost'
                ? '<i data-lucide="search-x" style="width:14px;"></i> Hilang'
                : '<i data-lucide="package-check" style="width:14px;"></i> Ditemukan';
            marker.bindPopup(
                '<div style="min-width:160px;">' +
                '<strong>' + escapeHtml(p.item_name) + '</strong><br>' +
                '<span style="font-size:0.8rem;">' + icon + '</span><br>' +
                '<span style="font-size:0.8rem;color:#64748b;">' + escapeHtml(p.location) + '</span><br>' +
                '<a href="pages/detail.php?id=' + p.id + '" style="color:var(--primary);font-weight:600;font-size:0.85rem;">Lihat Detail →</a>' +
                '</div>'
            );
            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
        } else {
            map.setView([-6.2, 106.816666], 12);
        }
        setTimeout(function() { map.invalidateSize(); }, 200);
        return map;
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    var indexMap = initIndexMap();
    lucide.createIcons();

    document.getElementById('toggle-map').addEventListener('click', function() {
        var mapEl = document.getElementById('map-index');
        var hidden = mapEl.style.display === 'none';
        mapEl.style.display = hidden ? 'block' : 'none';
        this.innerHTML = hidden
            ? '<i data-lucide="eye-off" style="width: 14px;"></i> Sembunyikan'
            : '<i data-lucide="eye" style="width: 14px;"></i> Tampilkan';
        lucide.createIcons();
        if (hidden) {
            setTimeout(function() { indexMap.invalidateSize(); }, 200);
        }
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

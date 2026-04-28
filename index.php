<?php
// index.php
include 'includes/header.php';

// Search & Filter Logic
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type   = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';

$sql = "SELECT r.*, u.username FROM reports r JOIN users u ON r.user_id = u.id WHERE 1=1";
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
    $sql .= " AND r.location LIKE ?";
    $params[] = "%$location%";
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
$loc_stmt = $conn->query("SELECT DISTINCT location FROM reports");
$locations = $loc_stmt->fetch_all(MYSQLI_ASSOC);
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
                <i data-lucide="map-pin" style="width: 18px;"></i>
                <input type="text" name="location" value="<?php echo $location; ?>" class="filter-input" placeholder="Lokasi...">
            </div>
            <button type="submit" class="btn btn-primary">
                Cari Barang
            </button>
        </form>
    </div>
</div>

<div class="report-grid animate-fade-in" style="animation-delay: 0.2s;">
    <?php if ($reports->num_rows > 0): ?>
        <?php while($row = $reports->fetch_assoc()): ?>
            <div class="report-card">
                <div class="card-image">
                    <span class="type-badge <?php echo $row['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                        <?php echo $row['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                    </span>
                    <?php if ($row['image_url']): ?>
                        <img src="uploads/<?php echo $row['image_url']; ?>" alt="<?php echo $row['item_name']; ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <i data-lucide="image-off" style="width: 48px; height: 48px;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <h3 class="card-title"><?php echo $row['item_name']; ?></h3>
                    <div class="card-info">
                        <div class="info-row">
                            <i data-lucide="map-pin"></i>
                            <span><?php echo $row['location']; ?></span>
                        </div>
                        <div class="info-row">
                            <i data-lucide="calendar"></i>
                            <span><?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <i data-lucide="user"></i>
                            <span>Oleh: <?php echo $row['username']; ?></span>
                        </div>
                    </div>
                    <a href="pages/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-block">
                        Lihat Detail
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 5rem; background: white; border-radius: var(--radius); border: 2px dashed var(--border);">
            <i data-lucide="package-search" style="width: 64px; height: 64px; color: var(--text-light); margin-bottom: 1rem;"></i>
            <h3>Tidak ada laporan ditemukan</h3>
            <p style="color: var(--text-light);">Coba ubah kata kunci atau filter pencarian Anda.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

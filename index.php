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

<div class="hero">
    <h1>Temukan Barang Anda yang Hilang</h1>
    <p>Platform komunitas untuk melaporkan dan mencari barang hilang atau ditemukan dengan cepat dan mudah.</p>
</div>

<form action="" method="GET" class="filters">
    <div class="form-group">
        <label>Cari Barang</label>
        <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Nama barang...">
    </div>
    <div class="form-group">
        <label>Jenis</label>
        <select name="type">
            <option value="">Semua</option>
            <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Hilang</option>
            <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Ditemukan</option>
        </select>
    </div>
    <div class="form-group">
        <label>Lokasi</label>
        <input type="text" name="location" value="<?php echo $location; ?>" placeholder="Lokasi...">
    </div>
    <div class="form-group" style="display: flex; align-items: flex-end;">
        <button type="submit" class="btn btn-primary btn-block">Filter</button>
    </div>
</form>

<div class="report-grid">
    <?php if ($reports->num_rows > 0): ?>
        <?php while($row = $reports->fetch_assoc()): ?>
            <div class="report-card">
                <div class="report-img-wrapper">
                    <div class="report-badge">
                        <span class="badge <?php echo $row['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                            <?php echo $row['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                        </span>
                    </div>
                    <?php if ($row['image_url']): ?>
                        <img src="uploads/<?php echo $row['image_url']; ?>" alt="<?php echo $row['item_name']; ?>" class="report-img">
                    <?php else: ?>
                        <div class="report-img" style="background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.875rem;">Tanpa Foto</div>
                    <?php endif; ?>
                </div>
                
                <div class="report-content">
                    <h3 class="report-title"><?php echo $row['item_name']; ?></h3>
                    <div class="report-meta">
                        <div class="meta-item">
                            <span>📍</span>
                            <span><?php echo $row['location']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span>📅</span>
                            <span><?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <span>👤</span>
                            <span><?php echo $row['username']; ?></span>
                        </div>
                    </div>
                    <a href="pages/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-block" style="margin-top: auto;">Lihat Detail</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem; background: white; border-radius: 1rem;">
            <p>Tidak ada laporan ditemukan.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

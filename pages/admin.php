<?php
// pages/admin.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect("../index.php", "Akses ditolak. Anda bukan admin.", "danger");
}

// Filter admin
$f_type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$f_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$f_search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Stats
$total_reports = $conn->query("SELECT COUNT(*) as total FROM reports")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_lost = $conn->query("SELECT COUNT(*) as total FROM reports WHERE type='lost'")->fetch_assoc()['total'];
$total_found = $conn->query("SELECT COUNT(*) as total FROM reports WHERE type='found'")->fetch_assoc()['total'];

// Filtered reports
$sql = "SELECT r.*, u.username FROM reports r JOIN users u ON r.user_id = u.id WHERE 1=1";
$params = [];
$types = "";
if ($f_type) { $sql .= " AND r.type = ?"; $params[] = $f_type; $types .= "s"; }
if ($f_status) { $sql .= " AND r.status = ?"; $params[] = $f_status; $types .= "s"; }
if ($f_search) { $sql .= " AND r.item_name LIKE ?"; $params[] = "%$f_search%"; $types .= "s"; }
$sql .= " ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result();

include '../includes/header.php';
?>

<div style="margin: 2rem 0;">
    <h1 style="color: var(--text);">Admin Dashboard</h1>
    <p style="color: var(--text-light);">Kelola semua laporan dan pantau aktivitas sistem.</p>
</div>

<div class="filters" style="text-align: center;">
    <div class="stat-card" style="padding: 1rem; background: var(--surface); border-radius: 1rem; box-shadow: var(--shadow); border: 1px solid var(--border);">
        <h3 style="font-size: 2rem; color: var(--primary);"><?php echo $total_reports; ?></h3>
        <p style="color: var(--text-light);">Total Laporan</p>
    </div>
    <div class="stat-card" style="padding: 1rem; background: var(--surface); border-radius: 1rem; box-shadow: var(--shadow); border: 1px solid var(--border);">
        <h3 style="font-size: 2rem; color: var(--danger);"><?php echo $total_lost; ?></h3>
        <p style="color: var(--text-light);">Barang Hilang</p>
    </div>
    <div class="stat-card" style="padding: 1rem; background: var(--surface); border-radius: 1rem; box-shadow: var(--shadow); border: 1px solid var(--border);">
        <h3 style="font-size: 2rem; color: var(--success);"><?php echo $total_found; ?></h3>
        <p style="color: var(--text-light);">Barang Ditemukan</p>
    </div>
    <div class="stat-card" style="padding: 1rem; background: var(--surface); border-radius: 1rem; box-shadow: var(--shadow); border: 1px solid var(--border);">
        <h3 style="font-size: 2rem; color: var(--text);"><?php echo $total_users; ?></h3>
        <p style="color: var(--text-light);">Total User</p>
    </div>
</div>

<form method="GET" class="search-container" style="margin-top: 2rem;">
    <div class="filters">
        <div class="filter-group">
            <i data-lucide="search" style="width: 18px;"></i>
            <input type="text" name="search" value="<?php echo e($f_search); ?>" class="filter-input" placeholder="Cari nama barang...">
        </div>
        <div class="filter-group">
            <i data-lucide="tag" style="width: 18px;"></i>
            <select name="type" class="filter-input">
                <option value="">Semua Jenis</option>
                <option value="lost" <?php echo $f_type === 'lost' ? 'selected' : ''; ?>>Barang Hilang</option>
                <option value="found" <?php echo $f_type === 'found' ? 'selected' : ''; ?>>Barang Ditemukan</option>
            </select>
        </div>
        <div class="filter-group">
            <i data-lucide="circle-dot" style="width: 18px;"></i>
            <select name="status" class="filter-input">
                <option value="">Semua Status</option>
                <option value="open" <?php echo $f_status === 'open' ? 'selected' : ''; ?>>Terbuka</option>
                <option value="resolved" <?php echo $f_status === 'resolved' ? 'selected' : ''; ?>>Selesai</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-search">Filter</button>
    </div>
</form>

<div class="admin-table-container" style="background: var(--surface); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow); margin-top: 2rem; border: 1px solid var(--border);">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: var(--bg); color: var(--text);">
            <tr>
                <th style="padding: 1rem;">ID</th>
                <th style="padding: 1rem;">Barang</th>
                <th style="padding: 1rem;">Jenis</th>
                <th style="padding: 1rem;">Status</th>
                <th style="padding: 1rem;">Lokasi</th>
                <th style="padding: 1rem;">User</th>
                <th style="padding: 1rem;">Tanggal</th>
                <th style="padding: 1rem;">Aksi</th>
            </tr>
        </thead>
        <tbody style="color: var(--text);">
            <?php while($row = $reports->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;">#<?php echo $row['id']; ?></td>
                    <td style="padding: 1rem; font-weight: 600;"><?php echo e($row['item_name']); ?></td>
                    <td style="padding: 1rem;">
                        <span class="badge <?php echo $row['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                            <?php echo e(ucfirst($row['type'])); ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;
                            <?php echo $row['status'] === 'resolved' ? 'background:rgba(16,185,129,0.1);color:var(--success);' : 'background:rgba(100,116,139,0.1);color:var(--text-light);'; ?>">
                            <?php echo $row['status'] === 'resolved' ? 'Selesai' : 'Terbuka'; ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;"><?php echo e($row['location']); ?></td>
                    <td style="padding: 1rem;"><?php echo e($row['username']); ?></td>
                    <td style="padding: 1rem;"><?php echo date('d/m/y', strtotime($row['created_at'])); ?></td>
                    <td style="padding: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="detail.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Detail</a>
                        <form action="update_status.php" method="POST" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="<?php echo $row['status'] === 'resolved' ? 'open' : 'resolved'; ?>">
                            <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; background: var(--bg);">
                                <?php echo $row['status'] === 'resolved' ? 'Buka' : 'Selesai'; ?>
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="confirmDelete(<?php echo (int)$row['id']; ?>)">Hapus</button>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($reports->num_rows === 0): ?>
                <tr><td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-light);">Tidak ada laporan sesuai filter.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: "Laporan ini akan dihapus secara permanen dari sistem!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_report.php?id=' + id;
        }
    })
}
</script>

<?php include '../includes/footer.php'; ?>

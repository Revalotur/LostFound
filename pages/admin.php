<?php
// pages/admin.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect("../index.php", "Akses ditolak. Anda bukan admin.", "danger");
}

// Stats
$total_reports = $conn->query("SELECT COUNT(*) as total FROM reports")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_lost = $conn->query("SELECT COUNT(*) as total FROM reports WHERE type='lost'")->fetch_assoc()['total'];
$total_found = $conn->query("SELECT COUNT(*) as total FROM reports WHERE type='found'")->fetch_assoc()['total'];

// All reports
$reports = $conn->query("SELECT r.*, u.username FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");

include '../includes/header.php';
?>

<div style="margin: 2rem 0;">
    <h1>Admin Dashboard</h1>
    <p>Kelola semua laporan dan pantau aktivitas sistem.</p>
</div>

<div class="filters" style="text-align: center;">
    <div class="stat-card" style="padding: 1rem; background: white; border-radius: 1rem; box-shadow: var(--shadow);">
        <h3 style="font-size: 2rem; color: var(--primary);"><?php echo $total_reports; ?></h3>
        <p>Total Laporan</p>
    </div>
    <div class="stat-card" style="padding: 1rem; background: white; border-radius: 1rem; box-shadow: var(--shadow);">
        <h3 style="font-size: 2rem; color: var(--danger);"><?php echo $total_lost; ?></h3>
        <p>Barang Hilang</p>
    </div>
    <div class="stat-card" style="padding: 1rem; background: white; border-radius: 1rem; box-shadow: var(--shadow);">
        <h3 style="font-size: 2rem; color: var(--success);"><?php echo $total_found; ?></h3>
        <p>Barang Ditemukan</p>
    </div>
    <div class="stat-card" style="padding: 1rem; background: white; border-radius: 1rem; box-shadow: var(--shadow);">
        <h3 style="font-size: 2rem; color: var(--text);"><?php echo $total_users; ?></h3>
        <p>Total User</p>
    </div>
</div>

<div class="admin-table-container" style="background: white; border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow); margin-top: 2rem;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: #f1f5f9;">
            <tr>
                <th style="padding: 1rem;">ID</th>
                <th style="padding: 1rem;">Barang</th>
                <th style="padding: 1rem;">Jenis</th>
                <th style="padding: 1rem;">Lokasi</th>
                <th style="padding: 1rem;">User</th>
                <th style="padding: 1rem;">Tanggal</th>
                <th style="padding: 1rem;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $reports->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;">#<?php echo $row['id']; ?></td>
                    <td style="padding: 1rem; font-weight: 600;"><?php echo $row['item_name']; ?></td>
                    <td style="padding: 1rem;">
                        <span class="badge <?php echo $row['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                            <?php echo $row['type']; ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;"><?php echo $row['location']; ?></td>
                    <td style="padding: 1rem;"><?php echo $row['username']; ?></td>
                    <td style="padding: 1rem;"><?php echo date('d/m/y', strtotime($row['created_at'])); ?></td>
                    <td style="padding: 1rem; display: flex; gap: 0.5rem;">
                        <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn" style="background: #e2e8f0; padding: 0.3rem 0.6rem; font-size: 0.8rem;">Detail</a>
                        <a href="delete_report.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="return confirm('Hapus laporan ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

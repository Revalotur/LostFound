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

<div class="admin-table-container" style="background: var(--surface); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow); margin-top: 2rem; border: 1px solid var(--border);">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: var(--bg); color: var(--text);">
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
        <tbody style="color: var(--text);">
            <?php while($row = $reports->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;">#<?php echo $row['id']; ?></td>
                    <td style="padding: 1rem; font-weight: 600;"><?php echo $row['item_name']; ?></td>
                    <td style="padding: 1rem;">
                        <span class="badge <?php echo $row['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                            <?php echo ucfirst($row['type']); ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;"><?php echo $row['location']; ?></td>
                    <td style="padding: 1rem;"><?php echo $row['username']; ?></td>
                    <td style="padding: 1rem;"><?php echo date('d/m/y', strtotime($row['created_at'])); ?></td>
                    <td style="padding: 1rem; display: flex; gap: 0.5rem;">
                        <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Detail</a>
                        <button type="button" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="confirmDelete(<?php echo $row['id']; ?>)">Hapus</button>
                    </td>
                </tr>
            <?php endwhile; ?>
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

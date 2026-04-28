<?php
// pages/detail.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ../index.php");
    exit();
}

// Get Report Detail
$stmt = $conn->prepare("SELECT r.*, u.username, u.email, u.contact FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    redirect("../index.php", "Laporan tidak ditemukan.", "danger");
}

// Get Matching Reports
$matching_reports = get_matching_reports($conn, $report['item_name'], $id);

include '../includes/header.php';
?>

<div class="detail-container">
    <div class="detail-image-card">
        <?php if ($report['image_url']): ?>
            <img src="../uploads/<?php echo $report['image_url']; ?>" alt="<?php echo $report['item_name']; ?>" style="width: 100%; border-radius: 0.5rem; display: block;">
        <?php else: ?>
            <div style="width: 100%; height: 400px; background: #f1f5f9; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                Tidak ada foto untuk laporan ini
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-info-card">
        <span class="badge <?php echo $report['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>" style="font-size: 0.875rem; padding: 0.5rem 1rem; margin-bottom: 1.5rem;">
            <?php echo $report['type'] === 'lost' ? 'Barang Hilang' : 'Barang Ditemukan'; ?>
        </span>
        <h1 style="font-size: 2.25rem; font-weight: 800; margin-bottom: 2rem; letter-spacing: -0.025em; line-height: 1.2;"><?php echo $report['item_name']; ?></h1>
        
        <div class="info-grid">
            <div class="info-item">
                <span style="font-size: 1.25rem;">📍</span>
                <div>
                    <label style="margin-bottom: 0; color: var(--text-light);">Lokasi Kejadian</label>
                    <strong style="display: block;"><?php echo $report['location']; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <span style="font-size: 1.25rem;">📅</span>
                <div>
                    <label style="margin-bottom: 0; color: var(--text-light);">Tanggal Laporan</label>
                    <strong style="display: block;"><?php echo date('d F Y', strtotime($report['event_date'])); ?></strong>
                </div>
            </div>
            <div class="info-item">
                <span style="font-size: 1.25rem;">👤</span>
                <div>
                    <label style="margin-bottom: 0; color: var(--text-light);">Dilaporkan Oleh</label>
                    <strong style="display: block;"><?php echo $report['username']; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <span style="font-size: 1.25rem;">📧</span>
                <div>
                    <label style="margin-bottom: 0; color: var(--text-light);">Email Kontak</label>
                    <a href="mailto:<?php echo $report['email']; ?>" style="color: var(--primary); font-weight: 700; display: block;"><?php echo $report['email']; ?></a>
                </div>
            </div>
            <?php if (!empty($report['contact'])): ?>
            <div class="info-item">
                <span style="font-size: 1.25rem;">📱</span>
                <div>
                    <label style="margin-bottom: 0; color: var(--text-light);">Kontak Lain</label>
                    <strong style="display: block;"><?php echo $report['contact']; ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="description-box">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">Deskripsi Barang:</h3>
            <div class="description-content">
                <?php echo $report['description']; ?>
            </div>
        </div>

        <?php if (is_admin() || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $report['user_id'])): ?>
            <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
                <a href="delete_report.php?id=<?php echo $report['id']; ?>" class="btn btn-danger" style="flex: 1;" onclick="return confirm('Hapus laporan ini?')">Hapus Laporan</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($matching_reports)): ?>
<div class="matching-section">
    <h2 class="matching-title">
        <span style="font-size: 1.5rem;">🔍</span> Barang yang Mungkin Cocok
    </h2>
    <div class="matching-grid">
        <?php foreach ($matching_reports as $match): ?>
            <div class="report-card" style="border: 2px solid var(--primary);">
                <div class="report-content">
                    <span class="badge <?php echo $match['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                        <?php echo $match['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                    </span>
                    <h4 class="report-title"><?php echo $match['item_name']; ?></h4>
                    <p style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 0.5rem;">📍 <?php echo $match['location']; ?></p>
                    <a href="detail.php?id=<?php echo $match['id']; ?>" class="btn btn-primary btn-block" style="padding: 0.4rem; font-size: 0.9rem;">Lihat</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

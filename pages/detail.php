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

<div class="detail-container animate-fade-in">
    <div class="detail-image-card">
        <?php if ($report['image_url']): ?>
            <img src="../uploads/<?php echo $report['image_url']; ?>" alt="<?php echo $report['item_name']; ?>" style="width: 100%; border-radius: var(--radius); display: block; box-shadow: var(--shadow-lg);">
        <?php else: ?>
            <div style="width: 100%; height: 400px; background: #f1f5f9; border-radius: var(--radius); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; border: 2px dashed var(--border);">
                <i data-lucide="image-off" style="width: 64px; height: 64px; margin-bottom: 1rem;"></i>
                <p>Tidak ada foto untuk laporan ini</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-info-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <span class="type-badge <?php echo $report['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                <?php echo $report['type'] === 'lost' ? 'Barang Hilang' : 'Barang Ditemukan'; ?>
            </span>
            <span style="font-size: 0.875rem; color: var(--text-light);">
                ID Laporan: #<?php echo $report['id']; ?>
            </span>
        </div>
        
        <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 2rem; letter-spacing: -0.025em; line-height: 1.1; color: var(--text-dark);"><?php echo $report['item_name']; ?></h1>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="map-pin"></i>
                </div>
                <div>
                    <label>Lokasi Kejadian</label>
                    <strong><?php echo $report['location']; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="calendar"></i>
                </div>
                <div>
                    <label>Tanggal Kejadian</label>
                    <strong><?php echo date('d F Y', strtotime($report['event_date'])); ?></strong>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="user"></i>
                </div>
                <div>
                    <label>Dilaporkan Oleh</label>
                    <strong><?php echo $report['username']; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="mail"></i>
                </div>
                <div>
                    <label>Email Kontak</label>
                    <a href="mailto:<?php echo $report['email']; ?>"><?php echo $report['email']; ?></a>
                </div>
            </div>
            <?php if (!empty($report['contact'])): ?>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="phone"></i>
                </div>
                <div>
                    <label>Kontak Lain</label>
                    <strong><?php echo $report['contact']; ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="description-box" style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: var(--radius); border: 1px solid var(--border);">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="align-left" style="width: 18px;"></i>
                Deskripsi Barang
            </h3>
            <div style="line-height: 1.7; color: var(--text);">
                <?php echo nl2br(htmlspecialchars($report['description'])); ?>
            </div>
        </div>

        <?php if (is_admin() || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $report['user_id'])): ?>
            <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
                <a href="delete_report.php?id=<?php echo $report['id']; ?>" class="btn btn-danger btn-block" onclick="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                    <i data-lucide="trash-2" style="width: 18px;"></i>
                    Hapus Laporan
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($matching_reports)): ?>
<div class="matching-section animate-fade-in" style="animation-delay: 0.3s;">
    <h2 class="matching-title" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
        <i data-lucide="sparkles" style="color: var(--primary);"></i>
        Barang yang Mungkin Cocok
    </h2>
    <div class="matching-grid">
        <?php foreach ($matching_reports as $match): ?>
            <div class="report-card" style="border: 1px solid var(--primary-light);">
                <div class="card-body">
                    <span class="type-badge <?php echo $match['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>" style="font-size: 0.75rem; margin-bottom: 0.5rem;">
                        <?php echo $match['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                    </span>
                    <h4 class="card-title" style="font-size: 1.125rem; margin-bottom: 0.5rem;"><?php echo $match['item_name']; ?></h4>
                    <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.25rem;">
                        <i data-lucide="map-pin" style="width: 14px;"></i>
                        <?php echo $match['location']; ?>
                    </p>
                    <a href="detail.php?id=<?php echo $match['id']; ?>" class="btn btn-primary btn-block" style="padding: 0.5rem; font-size: 0.875rem;">
                        Lihat Detail
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

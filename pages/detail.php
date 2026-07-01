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

// Get Report Detail dengan face_verified
$stmt = $conn->prepare("SELECT r.*, u.username, u.email, u.contact, u.face_verified FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    redirect("../index.php", "Laporan tidak ditemukan.", "danger");
}

// Cek apakah ada room chat untuk posting ini (untuk pemilik posting)
$existing_room = null;
if (is_logged_in() && $_SESSION['user_id'] == $report['user_id']) {
    $stmt = $conn->prepare("SELECT * FROM chat_rooms WHERE report_id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $existing_room = $stmt->get_result()->fetch_assoc();
}

// Get Matching Reports
$matching_reports = get_matching_reports($conn, $report['item_name'], $id, $report['category']);

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
                <div class="info-content">
                    <label>Lokasi Kejadian</label>
                    <strong><?php echo $report['location']; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="calendar"></i>
                </div>
                <div class="info-content">
                    <label>Tanggal Kejadian</label>
                    <strong><?php echo date('d F Y', strtotime($report['event_date'])); ?></strong>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="user"></i>
                </div>
                <div class="info-content">
                    <label>Dilaporkan Oleh</label>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <strong><?php echo $report['username']; ?></strong>
                        <?php if ($report['face_verified']): ?>
                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                <i data-lucide="check-circle-2" style="width: 14px; height: 14px;"></i>
                                Identity Verified
                            </span>
                        <?php else: ?>
                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(100, 116, 139, 0.1); color: var(--text-light); padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                <i data-lucide="circle" style="width: 14px; height: 14px;"></i>
                                Not Verified
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="mail"></i>
                </div>
                <div class="info-content">
                    <label>Email Kontak</label>
                    <a href="mailto:<?php echo $report['email']; ?>"><?php echo $report['email']; ?></a>
                </div>
            </div>
            <?php if (!empty($report['contact'])): ?>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="phone"></i>
                </div>
                <div class="info-content">
                    <label>Kontak Lain</label>
                    <strong><?php echo $report['contact']; ?></strong>
                </div>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <div class="info-icon">
                    <i data-lucide="tag"></i>
                </div>
                <div class="info-content">
                    <label>Kategori</label>
                    <strong><?php echo $report['category'] ?? 'Lainnya'; ?></strong>
                </div>
            </div>
        </div>

        <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
        <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="map" style="width: 18px;"></i>
                Lokasi di Peta
            </h3>
            <div id="map-detail" style="height: 300px; border-radius: var(--radius); border: 1px solid var(--border);"></div>
        </div>
        <?php endif; ?>

        <div class="description-box" style="margin-top: 2rem; padding: 1.5rem; background: var(--bg); border-radius: var(--radius); border: 1px solid var(--border);">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="align-left" style="width: 18px;"></i>
                Deskripsi Barang
            </h3>
            <div style="line-height: 1.7; color: var(--text);">
                <?php echo nl2br(htmlspecialchars($report['description'])); ?>
            </div>
        </div>

        <!-- Tombol Hubungi Pemilik -->
        <?php if (is_logged_in() && $_SESSION['user_id'] != $report['user_id']): ?>
            <div style="margin-top: 2rem;">
                <button id="chat-btn" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.05rem;">
                    <i data-lucide="message-square" style="width: 20px; height: 20px;"></i>
                    Hubungi Pemilik
                </button>
            </div>
        <?php endif; ?>

        <!-- Tombol Lihat Chat untuk Pemilik Barang -->
        <?php if (is_logged_in() && $_SESSION['user_id'] == $report['user_id'] && $existing_room): ?>
            <div style="margin-top: 2rem;">
                <a href="chat.php?id=<?php echo $existing_room['id']; ?>" class="btn btn-success btn-block" style="padding: 15px; font-size: 1.05rem; background: #10b981;">
                    <i data-lucide="message-square" style="width: 20px; height: 20px;"></i>
                    Lihat Chat
                </a>
            </div>
        <?php endif; ?>

        <?php if (is_admin() || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $report['user_id'])): ?>
            <div class="detail-actions">
                <?php if ($report['status'] === 'open'): ?>
                    <form action="update_status.php" method="POST" id="form-resolved">
                        <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                        <input type="hidden" name="status" value="resolved">
                        <button type="button" class="btn btn-success btn-block" style="background: #10b981; color: white; border: none; font-weight: 600;" onclick="confirmResolved()">
                            <i data-lucide="check-circle" style="width: 18px;"></i>
                            Tandai Sudah Selesai
                        </button>
                    </form>
                <?php endif; ?>
                <a href="edit_report.php?id=<?php echo $report['id']; ?>" 
                   class="btn" style="flex: 1; background: var(--secondary); color: white; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i data-lucide="pencil" style="width: 18px;"></i>
                    Edit
                </a>
                <button type="button" class="btn btn-danger" style="flex: 1; font-weight: 600;" onclick="confirmDelete(<?php echo $report['id']; ?>)">
                    <i data-lucide="trash-2" style="width: 18px;"></i>
                    Hapus
                </button>
                <button type="button" class="btn" style="flex: 1; background: var(--secondary); color: white; font-weight: 600;" onclick="window.print()">
                    <i data-lucide="printer" style="width: 18px;"></i>
                    Cetak Brosur
                </button>
            </div>
        <?php endif; ?>

        <div class="brochure-footer">
            <p>Jika Anda menemukan barang ini, mohon segera hubungi kontak di atas.</p>
            <p style="font-weight: bold; margin-top: 1rem;">Terima Kasih atas Bantuannya!</p>
            <p style="font-size: 0.8rem; margin-top: 2rem;">Dicetak dari LostFound System - <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>
</div>

<?php if (!empty($matching_reports)): ?>
<div class="matching-section animate-fade-in" style="animation-delay: 0.3s; margin-top: 5rem;">
    <h2 class="matching-title" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
        <i data-lucide="sparkles" style="color: var(--primary);"></i>
        Barang yang Mungkin Cocok
    </h2>
    <div class="matching-grid">
        <?php foreach ($matching_reports as $match): ?>
            <div class="report-card">
                <div class="card-image" style="height: 150px;">
                    <span class="type-badge <?php echo $match['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>" style="font-size: 0.7rem;">
                        <?php echo $match['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                    </span>
                    <?php if ($match['image_url']): ?>
                        <img src="../uploads/<?php echo $match['image_url']; ?>" alt="<?php echo $match['item_name']; ?>">
<?php else: ?>
    <div style="width: 100%; height: 100%; background: var(--bg); display: flex; align-items: center; justify-content: center; color: var(--text-light);">
        <i data-lucide="image-off" style="width: 32px; height: 32px;"></i>
    </div>
<?php endif; ?>
                </div>
                <div class="card-body" style="padding: 1.25rem;">
                    <h4 class="card-title" style="font-size: 1rem; margin-bottom: 0.5rem;"><?php echo $match['item_name']; ?></h4>
                    <p style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.25rem;">
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

<?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
<script>
    var map = L.map('map-detail').setView([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add geocoder control for searching locations
    L.Control.geocoder({
        defaultMarkGeocode: false
    }).on('markgeocode', function(e) {
        var bbox = e.geocode.bbox;
        var poly = L.polygon([
            bbox.getSouthEast(),
            bbox.getNorthEast(),
            bbox.getNorthWest(),
            bbox.getSouthWest()
        ]).addTo(map);
        map.fitBounds(poly.getBounds());
    }).addTo(map);

    L.marker([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>]).addTo(map)
        .bindPopup('<?php echo addslashes($report['item_name']); ?>')
        .openPopup();
</script>
<?php endif; ?>

<script>
function confirmResolved() {
    Swal.fire({
        title: 'Barang sudah ditemukan?',
        text: "Status laporan akan diubah menjadi Selesai.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Sudah Selesai!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-resolved').submit();
        }
    })
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: "Data laporan ini akan dihapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Saja!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_report.php?id=' + id;
        }
    })
}

// Tombol Chat
<?php if (is_logged_in() && $_SESSION['user_id'] != $report['user_id']): ?>
document.getElementById('chat-btn').addEventListener('click', async function() {
    try {
        const response = await fetch('<?php echo BASE_URL; ?>api/create_room.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: <?php echo $report['id']; ?> })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '<?php echo BASE_URL; ?>pages/chat.php?id=' + result.room_id;
        } else if (result.redirect_to) {
            window.location.href = result.redirect_to + '?redirect_to=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: result.message || 'Terjadi kesalahan'
            });
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Terjadi kesalahan'
        });
    }
});
<?php endif; ?>
</script>

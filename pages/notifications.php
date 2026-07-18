<?php
// pages/notifications.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect("../auth/login.php", "Silakan login terlebih dahulu.", "danger");
}

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get semua notifikasi dengan pagination
$all_notifs = get_all_notifications($conn, $user_id, $limit, $offset);

// Get total count
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total_row = $total_result->fetch_assoc();
$total_notifs = $total_row['total'];
$total_pages = ceil($total_notifs / $limit);

include '../includes/header.php';
?>

<div style="max-width: 900px; margin: 2rem auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="color: var(--text);">Notifikasi Saya</h1>
        <?php if ($total_notifs > 0): ?>
            <button onclick="markAllAsRead()" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                ✓ Tandai Semua Sudah Dibaca
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($all_notifs)): ?>
        <div style="text-align: center; padding: 3rem; background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border);">
            <i data-lucide="inbox" style="width: 64px; height: 64px; color: var(--text-light); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text);">Tidak Ada Notifikasi</h3>
            <p style="color: var(--text-light); margin-bottom: 1.5rem;">Anda belum memiliki notifikasi apapun</p>
            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary" style="display: inline-block;">
                Kembali ke Beranda
            </a>
        </div>
    <?php else: ?>
        <div style="background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden;">
            <?php foreach ($all_notifs as $notif): ?>
                <div class="notif-card" onclick="goToReport(<?php echo $notif['report_id']; ?>)" style="padding: 1.5rem; border-bottom: 1px solid var(--border); cursor: pointer; transition: background 0.2s; display: flex; justify-content: space-between; align-items: start; gap: 1rem;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <span style="font-weight: 600; color: var(--primary);">
                                <?php echo $notif['type'] === 'match' ? '🔔 Laporan Cocok' : '✓ Laporan Selesai'; ?>
                            </span>
                            <?php if (!$notif['is_read']): ?>
                                <span style="background: var(--primary); color: white; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.3rem; font-weight: bold;">BARU</span>
                            <?php endif; ?>
                        </div>
                        <div style="color: var(--text); margin-bottom: 0.5rem; line-height: 1.6;">
                            <?php echo e_html($notif['message']); ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">
                            Barang: <strong><?php echo e($notif['item_name']); ?></strong>
                            <span style="margin: 0 0.5rem;">•</span>
                            <?php echo time_ago($notif['created_at']); ?>
                        </div>
                    </div>
                    <button onclick="event.stopPropagation(); deleteNotification(<?php echo (int)$notif['id']; ?>)" class="btn" style="background: var(--danger); color: white; padding: 0.4rem 0.8rem; font-size: 0.85rem; border: none; cursor: pointer;">
                        Hapus
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn" style="padding: 0.5rem 1rem;">← Sebelumnya</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <button class="btn btn-primary" style="padding: 0.5rem 1rem; cursor: default;">
                            <?php echo $i; ?>
                        </button>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" class="btn" style="padding: 0.5rem 1rem;">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn" style="padding: 0.5rem 1rem;">Selanjutnya →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    function goToReport(reportId) {
        window.location.href = '<?php echo BASE_URL; ?>pages/detail.php?id=' + reportId;
    }

    function deleteNotification(notifId) {
        if (confirm('Hapus notifikasi ini?')) {
            fetch('<?php echo BASE_URL; ?>api/delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_id: notifId,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    function markAllAsRead() {
        if (confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
            fetch('<?php echo BASE_URL; ?>api/mark_all_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>

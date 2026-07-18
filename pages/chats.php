<?php
// pages/chats.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../auth/login.php', 'Silakan login terlebih dahulu.', 'danger');
}

$user_id = $_SESSION['user_id'];

// Daftar room chat milik user, beserta partner, last message, dan jumlah unread
$stmt = $conn->prepare("
    SELECT cr.id AS room_id,
           cr.report_id,
           r.item_name,
           CASE
               WHEN cr.initiator_id = ? THEN cr.owner_id
               ELSE cr.initiator_id
           END AS partner_id,
           CASE
               WHEN cr.initiator_id = ? THEN u_owner.username
               ELSE u_init.username
           END AS partner_name,
           CASE
               WHEN cr.initiator_id = ? THEN u_owner.face_verified
               ELSE u_init.face_verified
           END AS partner_verified,
           (SELECT cm.message FROM chat_messages cm WHERE cm.room_id = cr.id ORDER BY cm.created_at DESC LIMIT 1) AS last_message,
           (SELECT cm.created_at FROM chat_messages cm WHERE cm.room_id = cr.id ORDER BY cm.created_at DESC LIMIT 1) AS last_time,
           (SELECT COUNT(*) FROM chat_messages cm2 WHERE cm2.room_id = cr.id AND cm2.sender_id != ? AND cm2.is_read = FALSE) AS unread
    FROM chat_rooms cr
    JOIN reports r ON cr.report_id = r.id
    JOIN users u_init ON cr.initiator_id = u_init.id
    JOIN users u_owner ON cr.owner_id = u_owner.id
    WHERE cr.initiator_id = ? OR cr.owner_id = ?
    ORDER BY last_time DESC, cr.id DESC
");
$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$rooms = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container" style="max-width: 800px; margin-top: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <i data-lucide="messages-square" style="width: 28px; color: var(--primary);"></i>
        <h1 style="color: var(--text); margin: 0; font-size: 1.75rem;">Percakapan Saya</h1>
    </div>

    <div style="background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden;">
        <?php if ($rooms->num_rows > 0): ?>
            <?php while ($room = $rooms->fetch_assoc()): ?>
                <a href="chat.php?id=<?php echo (int)$room['room_id']; ?>" style="display: flex; align-items: center; gap: 1rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); text-decoration: none; color: var(--text); transition: background 0.2s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        <?php echo strtoupper(substr(e($room['partner_name']), 0, 1)); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <strong style="font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo e($room['partner_name']); ?>
                            </strong>
                            <?php if ($room['partner_verified']): ?>
                                <i data-lucide="check-circle-2" style="width: 14px; height: 14px; color: var(--success);"></i>
                            <?php endif; ?>
                            <span style="font-size: 0.75rem; color: var(--text-light); margin-left: auto; white-space: nowrap;">
                                <?php echo $room['last_time'] ? time_ago($room['last_time']) : ''; ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 2px;">
                            <span style="font-size: 0.8rem; background: var(--bg); color: var(--text-light); padding: 1px 8px; border-radius: 10px; white-space: nowrap;">
                                <?php echo e($room['item_name']); ?>
                            </span>
                            <span style="font-size: 0.85rem; color: var(--text-light); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo e($room['last_message'] ?: 'Belum ada pesan'); ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($room['unread'] > 0): ?>
                        <span style="background: var(--danger); color: white; border-radius: 50%; min-width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; flex-shrink: 0;">
                            <?php echo $room['unread'] > 9 ? '9+' : $room['unread']; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="padding: 4rem 2rem; text-align: center; color: var(--text-light);">
                <i data-lucide="message-circle" style="width: 56px; height: 56px; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h3 style="color: var(--text);">Belum ada percakapan</h3>
                <p>Hubungi pemilik barang dari halaman detail laporan untuk memulai chat.</p>
                <a href="../index.php" class="btn btn-primary" style="margin-top: 1rem;">Lihat Laporan</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

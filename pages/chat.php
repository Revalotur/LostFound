<?php
// pages/chat.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../auth/login.php', 'Please login first', 'danger');
}

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Jika tidak ada room_id, redirect ke halaman utama
if ($room_id === 0) {
    redirect('../index.php', 'Invalid chat room', 'danger');
}

// Cek akses ke room DAN cek apakah user adalah initiator atau owner
$stmt = $conn->prepare("
    SELECT cr.*, 
           r.item_name,
           r.user_id as report_owner_id,
           CASE 
               WHEN cr.initiator_id = ? THEN cr.owner_id
               ELSE cr.initiator_id 
           END as chat_partner_id
    FROM chat_rooms cr
    JOIN reports r ON cr.report_id = r.id
    WHERE cr.id = ? AND (cr.initiator_id = ? OR cr.owner_id = ?)
");
$user_id = $_SESSION['user_id'];
$stmt->bind_param("iiii", $user_id, $room_id, $user_id, $user_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    redirect('../index.php', 'Access denied', 'danger');
}

// HANYA USER YANG MENJADI INITIATOR (yang memulai chat) YANG HARUS VERIFIKASI
// User yang menjadi owner (pemilik barang) tidak perlu verifikasi untuk membalas
$is_initiator = $room['initiator_id'] === $user_id;
if ($is_initiator && !is_face_verified($conn)) {
    redirect('../pages/face_verification.php?redirect_to=' . urlencode($_SERVER['REQUEST_URI']), 'Please verify your face first', 'warning');
}

// Catat akses chat ke audit trail
log_chat_access($conn, $user_id, $room_id, 'access_chat');

// Dapatkan info partner
$stmt = $conn->prepare("SELECT username, face_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $room['chat_partner_id']);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin-top: 30px;">
    <div class="detail-container animate-fade-in" style="display: block; padding: 0; overflow: hidden; height: 80vh;">
        <!-- Header Chat -->
        <div style="padding: 20px 30px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="../index.php" class="btn" style="padding: 8px 12px; background: var(--bg);">
                    <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i>
                </a>
                <div>
                    <h2 style="margin: 0; font-size: 1.25rem; color: var(--text); display: flex; align-items: center; gap: 8px;">
                        <?php echo e($partner['username']); ?>
                        <?php if ($partner['face_verified']): ?>
                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                <i data-lucide="check-circle-2" style="width: 14px; height: 14px;"></i>
                                Verified
                            </span>
                        <?php else: ?>
                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(100, 116, 139, 0.1); color: var(--text-light); padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                <i data-lucide="circle" style="width: 14px; height: 14px;"></i>
                                Not Verified
                            </span>
                        <?php endif; ?>
                    </h2>
                    <p style="margin: 5px 0 0 0; color: var(--text-light); font-size: 0.875rem;">
                        Tentang: <?php echo e($room['item_name']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="messages-container" style="height: calc(100% - 140px); overflow-y: auto; padding: 30px; background: var(--bg);">
            <div id="messages-list" style="display: flex; flex-direction: column; gap: 15px;">
                <!-- Messages will be loaded here -->
            </div>
        </div>

        <!-- Input Area -->
        <div style="padding: 20px 30px; background: var(--surface); border-top: 1px solid var(--border);">
            <form id="message-form" style="display: flex; gap: 15px;">
                <input 
                    type="text" 
                    id="message-input" 
                    placeholder="Ketik pesan..." 
                    class="form-group"
                    style="flex: 1; padding: 15px 20px; border: 1px solid var(--border); border-radius: 25px; font-size: 1rem; outline: none; transition: all 0.3s; background: var(--bg);"
                >
                <button type="submit" class="btn btn-primary" style="border-radius: 50%; width: 50px; height: 50px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="send" style="width: 20px; height: 20px;"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const roomId = <?php echo $room_id; ?>;
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;
    let messagesLoaded = false;

    async function loadMessages() {
        try {
            const response = await fetch(`<?php echo BASE_URL; ?>api/get_messages.php?room_id=${roomId}`);
            const data = await response.json();
            
            if (data.success) {
                renderMessages(data.messages);
                if (!messagesLoaded) {
                    scrollToBottom();
                    messagesLoaded = true;
                }
            }
        } catch (err) {
            console.error('Error loading messages:', err);
        }
    }

    function renderMessages(messages) {
        const container = document.getElementById('messages-list');
        container.innerHTML = '';
        
        if (messages.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--text-light);">
                    <i data-lucide="message-square" style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>Belum ada pesan. Mulai percakapan!</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }
        
        messages.forEach(msg => {
            const isOwn = msg.sender_id === currentUserId;
            const msgEl = document.createElement('div');
            msgEl.style.cssText = `
                display: flex;
                flex-direction: column;
                align-items: ${isOwn ? 'flex-end' : 'flex-start'};
                max-width: 70%;
                margin-left: ${isOwn ? 'auto' : '0'};
            `;
            
            msgEl.innerHTML = `
                <div style="
                    background: ${isOwn ? 'var(--primary)' : 'var(--surface)'};
                    color: ${isOwn ? 'white' : 'var(--text)'};
                    padding: 12px 18px;
                    border-radius: ${isOwn ? '18px 18px 4px 18px' : '18px 18px 18px 4px'};
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                ">
                    <p style="margin: 0; line-height: 1.5;">${escapeHtml(msg.message)}</p>
                </div>
                <span style="font-size: 0.75rem; color: var(--text-light); margin-top: 5px; padding: 0 5px;">
                    ${formatTime(msg.created_at)}
                </span>
            `;
            
            container.appendChild(msgEl);
        });
        
        lucide.createIcons();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    function scrollToBottom() {
        const container = document.getElementById('messages-container');
        container.scrollTop = container.scrollHeight;
    }

    // Send message
    document.getElementById('message-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const input = document.getElementById('message-input');
        const message = input.value.trim();
        
        if (!message) return;
        
        input.value = '';
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>api/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    room_id: roomId,
                    message: message,
                    csrf_token: csrfToken
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadMessages();
                setTimeout(scrollToBottom, 100);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: result.message || 'Gagal mengirim pesan'
                });
            }
        } catch (err) {
            console.error('Error sending message:', err);
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan'
            });
        }
    });

    // Load messages initially and poll for updates
    loadMessages();
    setInterval(loadMessages, 3000);
</script>

<?php include '../includes/footer.php'; ?>

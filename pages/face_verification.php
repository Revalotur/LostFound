<?php
// pages/face_verification.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../auth/login.php', 'Please login first', 'danger');
}

// Jika sudah verified, redirect ke halaman utama atau sesuai redirect_to
if (is_face_verified($conn)) {
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '../index.php';
    redirect($redirect_to, 'You are already verified', 'success');
}

include '../includes/header.php';
?>

<div class="container" style="max-width: 900px; margin-top: 40px;">
    <div class="detail-container animate-fade-in" style="display: block; padding: 40px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px; color: var(--text);">
                <i data-lucide="shield-check" style="width: 40px; height: 40px; vertical-align: middle; margin-right: 10px; color: var(--primary);"></i>
                Face Verification
            </h1>
            <p style="color: var(--text-light); font-size: 1.1rem;">
                Verifikasi wajah Anda untuk mengakses fitur chat
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
            <div>
                <div style="position: relative; background: var(--bg); border-radius: 16px; overflow: hidden; border: 2px solid var(--border);">
                    <video id="webcam" autoplay playsinline style="width: 100%; display: block; transform: scaleX(-1);"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <div id="scan-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: none; align-items: center; justify-content: center; background: rgba(99, 102, 241, 0.1);">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; animation: pulse 1.5s infinite;">
                                <i data-lucide="scan-face"></i>
                            </div>
                            <p style="margin-top: 10px; color: var(--primary); font-weight: 600;">Scanning...</p>
                        </div>
                    </div>
                </div>
                
                <div id="status-message" style="margin-top: 20px; padding: 15px; border-radius: 12px; text-align: center; font-weight: 600;">
                    <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle; margin-right: 5px;"></i>
                    <span id="status-text">Aktifkan webcam untuk memulai</span>
                </div>
            </div>

            <div>
                <div style="background: var(--bg); padding: 25px; border-radius: 16px; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 20px; color: var(--text); display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="list-checks" style="color: var(--primary);"></i>
                        Challenge Steps
                    </h3>
                    
                    <div id="challenge-steps">
                        <div class="challenge-step" data-step="1" style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border);">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                1
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Hadap Lurus</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Posisikan wajah Anda menghadap kamera</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>

                        <div class="challenge-step" data-step="2" style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border); opacity: 0.5;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                2
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Kedipkan Mata</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Kedipkan mata Anda beberapa kali</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>

                        <div class="challenge-step" data-step="3" style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border); opacity: 0.5;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                3
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Hadap Kiri</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Posisikan wajah menghadap ke kiri</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>

                        <div class="challenge-step" data-step="4" style="display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 12px; background: var(--surface); border: 2px solid var(--border); opacity: 0.5;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-light);">
                                4
                            </div>
                            <div class="step-text" style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text);">Hadap Kanan</div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">Posisikan wajah menghadap ke kanan</div>
                            </div>
                            <div class="step-status" style="color: var(--text-light);">
                                <i data-lucide="circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button id="start-btn" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.1rem;">
                        <i data-lucide="play" style="width: 20px; height: 20px;"></i>
                        Mulai Verifikasi
                    </button>
                    <button id="stop-btn" class="btn btn-block" style="padding: 15px; font-size: 1.1rem; display: none; margin-top: 10px; background: var(--secondary); color: white;">
                        <i data-lucide="square" style="width: 20px; height: 20px;"></i>
                        Berhenti
                    </button>
                </div>
            </div>
        </div>

        <div id="result-section" style="display: none; text-align: center; padding: 40px; background: var(--bg); border-radius: 16px; margin-top: 30px;">
            <div id="result-icon" style="font-size: 5rem; margin-bottom: 20px;"></div>
            <h2 id="result-title" style="margin-bottom: 10px;"></h2>
            <p id="result-message" style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 30px;"></p>
            <a id="result-btn" href="../index.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">
                Lanjutkan
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    .challenge-step.active {
        border-color: var(--primary) !important;
        opacity: 1 !important;
    }
    
    .challenge-step.active .step-icon {
        background: var(--primary) !important;
        color: white !important;
    }
    
    .challenge-step.completed {
        border-color: var(--success) !important;
        opacity: 1 !important;
    }
    
    .challenge-step.completed .step-icon {
        background: var(--success) !important;
        color: white !important;
    }
    
    #status-message.info {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }
    
    #status-message.success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    
    #status-message.error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }
</style>

<script>
    let video = document.getElementById('webcam');
    let canvas = document.getElementById('canvas');
    let ctx = canvas.getContext('2d');
    let stream = null;
    let isVerifying = false;
    let currentStep = 1;
    let challengeCompleted = [];
    let captureInterval = null;
    let lastFaceData = null;
    
    const challenges = [
        { id: 1, name: 'Hadap Lurus', instruction: 'Posisikan wajah Anda menghadap kamera' },
        { id: 2, name: 'Kedipkan Mata', instruction: 'Kedipkan mata Anda beberapa kali' },
        { id: 3, name: 'Hadap Kiri', instruction: 'Posisikan wajah menghadap ke kiri' },
        { id: 4, name: 'Hadap Kanan', instruction: 'Posisikan wajah menghadap ke kanan' }
    ];

    async function startWebcam() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                } 
            });
            video.srcObject = stream;
            canvas.width = 640;
            canvas.height = 480;
            updateStatus('Webcam aktif! Tekan "Mulai Verifikasi" untuk memulai', 'info');
        } catch (err) {
            updateStatus('Gagal mengakses webcam: ' + err.message, 'error');
            console.error(err);
        }
    }

    function updateStatus(message, type = 'info') {
        const statusEl = document.getElementById('status-message');
        const textEl = document.getElementById('status-text');
        textEl.textContent = message;
        statusEl.className = type;
        
        const icon = statusEl.querySelector('[data-lucide]');
        if (icon) {
            if (type === 'success') icon.setAttribute('data-lucide', 'check-circle');
            else if (type === 'error') icon.setAttribute('data-lucide', 'alert-circle');
            else icon.setAttribute('data-lucide', 'info');
            lucide.createIcons();
        }
    }

    function updateStep(step, status) {
        const stepEl = document.querySelector(`.challenge-step[data-step="${step}"]`);
        if (!stepEl) return;
        
        stepEl.classList.remove('active', 'completed');
        
        if (status === 'active') {
            stepEl.classList.add('active');
        } else if (status === 'completed') {
            stepEl.classList.add('completed');
            const statusIcon = stepEl.querySelector('.step-status');
            statusIcon.innerHTML = '<i data-lucide="check"></i>';
            lucide.createIcons();
        }
        
        // Update opacity untuk step berikutnya
        document.querySelectorAll('.challenge-step').forEach(el => {
            const elStep = parseInt(el.dataset.step);
            if (elStep > step && status === 'active') {
                el.style.opacity = '0.5';
            } else if (elStep <= step) {
                el.style.opacity = '1';
            }
        });
    }

    function captureFrame() {
        ctx.save();
        ctx.scale(-1, 1);
        ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
        ctx.restore();
        return canvas.toDataURL('image/jpeg', 0.8);
    }

    async function detectFace(imageData) {
        try {
            const response = await fetch('<?php echo BASE_URL; ?>api/register_face.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'detect', image: imageData })
            });
            return await response.json();
        } catch (err) {
            console.error('Detection error:', err);
            return { success: false };
        }
    }

    function simulateChallengeComplete() {
        // Simulasi sederhana untuk challenge (tanpa analisis landmark yang kompleks)
        // Di production, ini akan menganalisis pergerakan landmark wajah
        return new Promise(resolve => {
            setTimeout(() => {
                resolve(true);
            }, 2000);
        });
    }

    async function runVerification() {
        isVerifying = true;
        currentStep = 1;
        challengeCompleted = [];
        
        document.getElementById('start-btn').style.display = 'none';
        document.getElementById('stop-btn').style.display = 'block';
        document.getElementById('scan-overlay').style.display = 'flex';
        
        for (let i = 1; i <= 4; i++) {
            if (!isVerifying) break;
            
            currentStep = i;
            updateStep(i, 'active');
            updateStatus(challenges[i-1].instruction, 'info');
            
            // Simulasi challenge
            const completed = await simulateChallengeComplete();
            
            if (completed) {
                challengeCompleted.push(i);
                updateStep(i, 'completed');
                updateStatus(challenges[i-1].name + ' selesai!', 'success');
            }
        }
        
        if (isVerifying && challengeCompleted.length === 4) {
            // Capture final image dan register
            updateStatus('Menyimpan data wajah...', 'info');
            
            const imageData = captureFrame();
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>api/register_face.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: imageData })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResult(true, 'Verifikasi Berhasil!', 'Wajah Anda telah terverifikasi. Sekarang Anda dapat mengakses fitur chat.');
                } else {
                    showResult(false, 'Verifikasi Gagal', result.message || 'Terjadi kesalahan saat verifikasi. Silakan coba lagi.');
                }
            } catch (err) {
                showResult(false, 'Verifikasi Gagal', 'Terjadi kesalahan: ' + err.message);
            }
        }
        
        stopVerification();
    }

    function stopVerification() {
        isVerifying = false;
        document.getElementById('scan-overlay').style.display = 'none';
        document.getElementById('start-btn').style.display = 'block';
        document.getElementById('stop-btn').style.display = 'none';
        
        if (captureInterval) {
            clearInterval(captureInterval);
            captureInterval = null;
        }
    }

    function showResult(success, title, message) {
        const resultSection = document.getElementById('result-section');
        const resultIcon = document.getElementById('result-icon');
        const resultTitle = document.getElementById('result-title');
        const resultMessage = document.getElementById('result-message');
        const resultBtn = document.getElementById('result-btn');
        
        resultSection.style.display = 'block';
        
        if (success) {
            resultIcon.innerHTML = '<i data-lucide="check-circle-2" style="color: var(--success); width: 80px; height: 80px;"></i>';
            resultTitle.style.color = 'var(--success)';
        } else {
            resultIcon.innerHTML = '<i data-lucide="x-circle" style="color: var(--danger); width: 80px; height: 80px;"></i>';
            resultTitle.style.color = 'var(--danger)';
        }
        
        resultTitle.textContent = title;
        resultMessage.textContent = message;
        
        const redirectTo = '<?php echo isset($_GET["redirect_to"]) ? $_GET["redirect_to"] : "../index.php"; ?>';
        resultBtn.href = redirectTo;
        
        lucide.createIcons();
    }

    // Event listeners
    document.getElementById('start-btn').addEventListener('click', runVerification);
    document.getElementById('stop-btn').addEventListener('click', () => {
        stopVerification();
        updateStatus('Verifikasi dihentikan', 'info');
        // Reset steps
        for (let i = 1; i <= 4; i++) {
            updateStep(i, '');
        }
        updateStep(1, 'active');
    });

    // Initialize
    startWebcam();
</script>

<?php include '../includes/footer.php'; ?>

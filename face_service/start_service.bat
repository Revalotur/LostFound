@echo off
REM ===================================================================
REM Start Face Verification Service (Flask + InsightFace)
REM Jalankan file ini agar layanan face recognition berjalan.
REM Pastikan dependency sudah terinstal:
REM   pip install -r requirements.txt
REM ===================================================================
cd /d "%~dp0"

echo Mencari Python...
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Python tidak ditemukan di PATH. Install Python 3.x dan tambahkan ke PATH.
    pause
    exit /b 1
)

echo Mengecek dependency...
python -c "import insightface, onnxruntime, cv2, flask" >nul 2>nul
if %errorlevel% neq 0 (
    echo WARNING: Dependency belum lengkap. Menginstal dari requirements.txt...
    python -m pip install -r requirements.txt
)

echo Menjalankan Face Verification Service di http://127.0.0.1:5000
echo (Biarkan window ini tetap terbuka selama aplikasi digunakan)
echo Tekan Ctrl+C untuk menghentikan.
echo ===================================================================
python app.py
pause

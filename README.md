# Panduan Instalasi Lost & Found System

Sistem Informasi Laporan Kehilangan dan Penemuan Barang Berbasis Web.

## Persyaratan
- XAMPP atau web server lain yang mendukung PHP 7.4+ dan MySQL.
- Browser modern (Chrome, Edge, Firefox).

## Langkah Instalasi
1. **Persiapkan Database**:
   - Buka phpMyAdmin (http://localhost/phpmyadmin).
   - Buat database baru dengan nama `lost_and_found_db`.
   - Import file `database.sql` yang ada di root folder ini ke database tersebut.

2. **Pindahkan Folder Proyek**:
   - Copy seluruh folder `BarangHilang` ke direktori `htdocs` di dalam folder instalasi XAMPP Anda (biasanya `C:\xampp\htdocs\BarangHilang`).

3. **Konfigurasi Database (Opsional)**:
   - Jika Anda menggunakan user/password database yang berbeda dari default (root / empty), sesuaikan di file `config/db.php`.

4. **Jalankan Aplikasi**:
   - Buka browser dan akses: `http://localhost/BarangHilang/`.

## Data Login Default (Admin)
- **Username**: `admin`
- **Password**: `admin123`

## Fitur Utama
- **Otentikasi**: Registrasi dan Login user.
- **Laporan**: Membuat laporan barang hilang atau ditemukan lengkap dengan foto.
- **Matching System**: Menampilkan barang yang mungkin cocok secara otomatis berdasarkan kemiripan nama.
- **Search & Filter**: Pencarian barang berdasarkan nama, jenis, dan lokasi secara real-time.
- **Admin Panel**: Dashboard khusus untuk memantau dan menghapus laporan.
- **Modern UI**: Desain responsif dan bersih menggunakan CSS Grid/Flexbox.

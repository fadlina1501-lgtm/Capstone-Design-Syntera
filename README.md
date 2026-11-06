# Stock Tracking Line AIJ — Capstone Design

Aplikasi Laravel untuk pencatatan dan traceability aliran material pada lima tahapan proses Line AIJ: Incoming Warehouse, Incoming Rack, Dandori, Assembly, dan Delivery.

## Teknologi

- Laravel 13 / PHP 8.3+ (gunakan PHP 8.4 untuk deployment Laravel Cloud)
- MySQL
- Frontend statis pada `public/css` dan `public/js` (tidak menggunakan Vite build untuk halaman aplikasi ini)
- QR pairing HP untuk membantu operator memindai barcode dari perangkat mobile
- QR Code lokal pada Part Tag yang dihasilkan Dandori

## Menjalankan lokal

1. Salin `.env.example` menjadi `.env`.
2. Buat database MySQL kosong dan sesuaikan `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD`.
3. Jalankan `composer install`.
4. Jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate --seed`.
6. Jalankan `php artisan optimize:clear` dan `php artisan serve --port=8000`.
7. Buka `http://127.0.0.1:8000`.

Seeder menyiapkan enam role, akun demo, master data, BOM, lokasi, dan Production Plan. Seeder tidak membuat stok/box awal. Untuk lingkungan production, atur secret `DEMO_USER_PASSWORD` (minimal 12 karakter) sebelum menjalankan `php artisan db:seed --force`.

## Hak akses

Enam role yang disediakan: Staf Administrasi, Operator Warehouse, Dandori, Operator Assembly, Operator Delivery, dan Atasan. Rincian akses ada pada [ROLE_ACCESS_MATRIX.md](ROLE_ACCESS_MATRIX.md).

## Fitur yang ditampilkan

- Master Data untuk Staf Administrasi; Atasan melihat saja.
- Incoming Warehouse dan Incoming Rack.
- Dandori dengan validasi BOM dan QR Part Tag.
- Assembly, Part NG, dan Delivery ke Outgoing Area serta Loading Area.
- Stock Monitoring, Stock Movement, Discrepancy berbasis Stock Opname, dan Stock Opname.
- Reports, notifikasi, serta QR pairing laptop–HP.

Mini Cycle Count dan halaman Riwayat Transaksi keseluruhan sudah dinonaktifkan dari menu dan route aplikasi. Tabel legacy Mini Cycle Count tetap dipertahankan di migration agar deployment tidak menghapus data lama secara destruktif.

## Deployment Laravel Cloud

Lihat [LARAVEL_CLOUD_DEPLOYMENT.md](LARAVEL_CLOUD_DEPLOYMENT.md) untuk konfigurasi Build Command, Deploy Command, database, PHP, dan environment production.

## Keamanan

Jangan commit `.env`, kredensial database, APP_KEY, token, atau file backup yang mengandung rahasia. Untuk repository Public, gunakan secret environment Laravel Cloud dan ubah password demo untuk production.

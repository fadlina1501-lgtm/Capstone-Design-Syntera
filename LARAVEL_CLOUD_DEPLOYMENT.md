# Panduan Deploy Stock Tracking Line AIJ ke Laravel Cloud

Panduan ini menyiapkan aplikasi Laravel 13 yang memakai aset statis dari `public/css` dan `public/js`. Aplikasi ini tidak memerlukan Vite untuk menghasilkan `public/build`.

## 1. Persiapan repository

Pastikan commit terbaru memuat: `bootstrap/cache/.gitignore`, `.env.example`, migration `2026_11_07_000001_create_sessions_table.php`, dan aset `public/js/qr-lite.js`. Jangan commit `.env`, `.git`, `vendor`, atau `node_modules`.

## 2. Buat aplikasi dan environment

1. Di Laravel Cloud, buat Application dari repository GitHub `Capstone-Design-Syntera` dan branch `main`.
2. Pilih PHP **8.4** (project membutuhkan PHP `^8.3`).
3. Buat/attach resource database **MySQL** ke environment `production`. Tunggu resource aktif sebelum deploy. Laravel Cloud akan menyediakan kredensial resource sebagai environment variables; jangan memakai `DB_HOST=127.0.0.1` dari laptop.
4. Pastikan `DB_CONNECTION=mysql`. Jika resource tidak mengisi variabel `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`, salin nilainya dari panel resource Cloud ke Environment Variables/Secrets.

## 3. Environment variables

Atur minimal:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://SUBDOMAIN-APP-ANDA.laravel.cloud
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mysql
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

- Ganti `APP_URL` dengan domain `.laravel.cloud` yang diberikan pada aplikasi.
- Laravel Cloud dapat membuat dan menyuntikkan `APP_KEY` secara otomatis saat deploy pertama. Jangan menyalin APP_KEY lokal ke production.
- Simpan `DEMO_USER_PASSWORD` sebagai **Secret**, bukan nilai yang dicatat di repository. Gunakan password acak minimal 12 karakter. Seeder production akan menolak berjalan jika secret ini kosong/terlalu pendek.
- `SESSION_DRIVER=database` dipakai karena project menyertakan migration tabel `sessions`.

## 4. Build Command

Buka Environment `production` → Settings → Build & Deploy/Build Commands, lalu gunakan:

```bash
mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX bootstrap/cache storage
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
```

Perintah pertama memastikan direktori cache dan storage tersedia sebelum Composer menjalankan script Laravel `package:discover`. File `bootstrap/cache/.gitignore` juga menjaga folder tersebut tetap tercatat di Git. `npm run build` tidak diperlukan karena CSS/JS aplikasi sudah disimpan langsung di folder `public`.

## 5. Deploy Command

Atur Deploy Command menjadi:

```bash
php artisan migrate --force
```

Jangan menjalankan `migrate:fresh` di production. Migrasi menambahkan tabel `sessions` tanpa menghapus tabel/data yang ada.

## 6. Seed master data demo satu kali

Setelah deployment pertama sukses dan database sudah terhubung, buka tab Commands pada Laravel Cloud dan jalankan satu kali:

```bash
php artisan db:seed --force
```

Seeder bersifat idempotent untuk role, akun, master data, BOM, lokasi, dan Production Plan; seeder tidak membuat box atau stok awal. `DEMO_USER_PASSWORD` harus sudah disetel sebagai Cloud Secret sebelum perintah ini dijalankan. Ganti password akun demo setelah demonstrasi jika aplikasi akan dipakai oleh pengguna lain.

## 7. Verifikasi setelah deploy

1. Buka URL HTTPS `.laravel.cloud` dan pastikan halaman login memuat CSS/JS.
2. Login menggunakan akun yang dibuat seeder dan password pada secret.
3. Uji Dashboard, Master Data, Incoming Warehouse, Incoming Rack, Dandori, Part Tag QR, Assembly, Part NG, Delivery, Stock Monitoring, Stock Movement, Discrepancy, Stock Opname, Reports, dan Notifikasi.
4. Uji QR pairing dari HP. Kamera browser membutuhkan HTTPS; domain Laravel Cloud menyediakan HTTPS.
5. Pastikan QR Part Tag yang tercetak dapat dipindai oleh halaman scanner.

## 8. Catatan penting

- `storage` pada filesystem instance cloud dapat bersifat sementara; jangan mengandalkan file lokal untuk dokumen penting yang harus bertahan setelah redeploy.
- Database production harus resource MySQL terlampir dan tidak boleh diarahkan ke database lokal laptop.
- Fitur Mini Cycle Count dan halaman Riwayat Transaksi keseluruhan telah dihapus dari UI dan route; tabel legacy Mini Cycle Count tetap dipertahankan untuk kompatibilitas data lama.

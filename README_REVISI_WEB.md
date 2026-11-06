# Stock Tracking Line AIJ — Paket Revisi Website

Proyek Laravel ini direvisi dengan menjadikan dokumen dan diagram Capstone sebagai acuan: AS-IS, TO-BE, ERD, Use Case enam aktor, dan Activity Diagram masing-masing aktor.

## 1. Acuan alur proses

1. **Incoming Warehouse** — barcode supplier sudah terpasang pada box. Operator Warehouse memindai barcode, sistem membaca identitas material dan mengisi informasi yang tersedia, operator memeriksa data, lalu menyimpan penerimaan ke lokasi Incoming Warehouse.
2. **Incoming Rack** — operator memindai barcode supplier yang sama ketika box dipindahkan dari Incoming Warehouse ke Incoming Rack. Sistem memperbarui saldo dan riwayat perpindahan.
3. **Dandori** — Dandori memilih tanggal/Production Plan dan Finished Good. Sistem menampilkan kebutuhan komponen dari BOM dan stok Incoming Rack. Dandori memindai barcode box komponen, sistem memvalidasi part, lokasi, dan jumlah, lalu mencatat persiapan serta menghasilkan Part Tag baru.
4. **Assembly** — Operator Assembly memindai Part Tag dari Dandori, mengisi jumlah Finished Good yang dihasilkan, dan menyimpan hasil Assembly. Pencatatan Part NG tersedia pada menu terpisah.
5. **Delivery** — Operator Delivery memilih tanggal Production Plan, memindai Part Tag FG untuk mencatat perpindahan Assembly ke Outgoing Area, lalu mencatat perpindahan berikutnya ke Loading Area.

**Identitas barcode tidak disamakan:** barcode supplier mengidentifikasi box komponen/HFG saat Incoming, Rack, dan Dandori. Part Tag adalah identitas hasil persiapan FG dari Dandori yang digunakan pada Assembly dan Delivery.

## 2. Hak akses

- Staf Administrasi: mengelola Master Data; melihat data transaksi; monitoring; Daily Production; Stock Opname; notifikasi; dan Reports.
- Operator Warehouse: mencatat Incoming Warehouse dan Incoming Rack; melihat monitoring; membuat Mini Cycle Count; melihat notifikasi.
- Dandori: melihat Production Plan; melakukan persiapan material dan membuat Part Tag; melihat monitoring dan notifikasi.
- Operator Assembly: mencatat Assembly dan Part NG secara terpisah; melihat Production Plan dan monitoring; dapat membuat Mini Cycle Count.
- Operator Delivery: mencatat Delivery dan perpindahan ke Loading Area; melihat Production Plan dan monitoring; dapat membuat Mini Cycle Count.
- Atasan: melihat Master Data dan transaksi; memantau persediaan/produksi; meninjau hasil, merevisi nilai Stock Actual bila diperlukan, lalu menyetujui Mini Cycle Count; melihat Reports dan notifikasi.

Admin dan Atasan hanya mendapat tampilan baca-saja pada halaman transaksi. Aksi simpan transaksi dibatasi pada role operasional.

## 3. Menjalankan pada XAMPP / Windows

1. Ekstrak ZIP ke folder kerja, misalnya `D:\SEMESTER 7\PAD\capstone_design_syntera`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buat database kosong bernama `stock_tracking_capstone` melalui phpMyAdmin.
4. Salin `.env.example` menjadi `.env`. Pastikan konfigurasi lokal menggunakan `APP_ENV=local` dan `APP_DEBUG=true`.
5. Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env` dengan konfigurasi MySQL lokal.
6. Buka terminal VS Code pada folder proyek hasil ekstrak paket revisi terbaru dan jalankan:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan optimize:clear
   ```

7. Jalankan Laravel:

   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```

8. Buka aplikasi di laptop melalui `http://127.0.0.1:8000`.

**Catatan aset frontend:** halaman menggunakan file statis yang sudah tersedia di `public/css` dan `public/js` melalui helper `asset()`. Aplikasi pada paket ini tidak memakai entrypoint Vite `resources/css/app.css` / `resources/js/app.js`. Script `npm run build` dan `npm run dev` pada paket ini sengaja tidak menjalankan Vite, sehingga langkah setup tidak mencari entrypoint yang memang tidak digunakan. Untuk menjalankan aplikasi, cukup gunakan file statis yang tersedia di `public/` dan jalankan Laravel.

Jika paket diekstrak tanpa folder `vendor`, jalankan `composer install`. Jika folder `node_modules` tidak tersedia, `npm install` tidak diperlukan untuk menjalankan aplikasi. Instalasi npm hanya relevan jika nanti ada pekerjaan pengembangan frontend tambahan.

## 4. Akun demo

Semua akun demo menggunakan password `Capstone123!`.

| Role | Username |
|---|---|
| Staf Administrasi | `admin` |
| Operator Warehouse | `warehouse` |
| Dandori | `dandori` |
| Operator Assembly | `assembly` |
| Operator Delivery | `delivery` |
| Atasan | `atasan` |

Ganti password dan konfigurasi keamanan sebelum penggunaan selain pengujian prototype.

## 5. Demo scanning dengan HP

1. Pastikan Laravel berjalan pada port 8000.
2. Buka terminal PowerShell kedua dan jalankan `ngrok http 8000`.
3. Buka aplikasi laptop melalui `http://127.0.0.1:8000`, login sebagai Operator Warehouse, lalu buka Incoming Warehouse.
4. Klik **Hubungkan HP untuk Scan**. Buka URL HTTPS pada HP, lalu tekan **Mulai Kamera**.
5. Di laptop, tampilkan salah satu gambar QR di folder `sample-supplier-qr`. Pindai QR dari layar laptop menggunakan HP.
6. Informasi barcode/part/supplier/quantity/PO/tanggal/urutan box/shelf akan diisikan ke form Incoming Warehouse. Periksa datanya dan klik **Simpan Penerimaan**.
7. Pada Incoming Rack, pindai barcode box yang sama, lalu catat perpindahannya.
8. Di Dandori, pilih Production Plan, masukkan kuantitas FG, dan pindai barcode supplier box komponen sesuai BOM. Sistem hanya menerima part yang dibutuhkan BOM dan box yang berada di Incoming Rack atau sisa box yang sah di Dandori.
9. Setelah persiapan berhasil, buka/print Part Tag. Di Assembly, pindai Part Tag tersebut dan catat qty FG produced. Pencatatan Part NG dilakukan di menu Part NG.
10. Di Delivery, pilih tanggal Production Plan, pindai Part Tag untuk mencatat Outgoing Area, lalu klik aksi pindah ke Loading Area pada baris produk yang sudah berada di Outgoing Area.

Folder `sample-supplier-qr` berisi QR demo fiktif dan payload yang dibaca scanner. QR demo ini **bukan** QR aktual pemasok perusahaan. Untuk QR supplier aktual, format payload perlu dipetakan berdasarkan hasil scan QR aslinya.

### Contoh uji end-to-end untuk FG-HND-730

Setelah empat box berikut masing-masing diproses melalui Incoming Warehouse lalu Incoming Rack, pilih Production Plan `FG-HND-730` dan siapkan Qty FG `1` pada Dandori. Pindai satu barcode per baris:

```text
SUP-DHN-26018-03
SUP-AST-26018-01
SUP-CND-26018-02
SUP-BRL-26018-03
```

Empat box tersebut mewakili `HF-HND-630` (1 unit), `CM-AST-401` (1 unit), `CM-CVR-180` (1 unit), dan `CM-PIN-310` (2 unit) sesuai BOM demo. Setelah Dandori menghasilkan Part Tag, gunakan Part Tag yang tercetak pada Assembly dan Delivery.

## 6. Database lama dan keamanan data

- Seeder terbaru membuat akun demo, master data sintetis, BOM, Production Plan, ManPower, dan lokasi proses.
- Seeder **tidak membuat stok/box awal otomatis**. Persediaan demo dimulai dari pemindaian barcode supplier pada Incoming Warehouse agar mengikuti alur TO-BE.
- Jika proyek dijalankan pada database yang sebelumnya sudah berisi demo lama (misalnya `DEMO-FG-001` atau opening stock), data lama tidak dihapus otomatis. Periksa dan backup database sebelum pembersihan manual.
- **Jangan menjalankan `php artisan migrate:fresh` pada database yang berisi data penting**, karena perintah tersebut menghapus seluruh tabel dan data.
- File `.env` tidak disertakan dalam paket karena memuat konfigurasi lokal. Buat file itu dari `.env.example` dan isi hanya dengan kredensial lokal milik pengguna.

## 7. Batasan pengujian

Fungsi transaksi mengikuti alur TO-BE pada prototype. Pengujian scanner HP tetap membutuhkan koneksi HTTPS (misalnya tunnel ngrok), izin kamera browser, dan koneksi internet yang dapat mengakses tunnel. Ketersediaan kamera/izin browser dan jaringan harus diuji pada perangkat yang digunakan.

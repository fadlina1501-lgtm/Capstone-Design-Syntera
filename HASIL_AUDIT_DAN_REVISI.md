# Hasil Audit dan Revisi Paket Website Stock Tracking

Tanggal paket: 5 November 2026

## Perbaikan pada paket ini

1. Memperketat route Riwayat Transaksi keseluruhan agar hanya dapat dibuka Staf Administrasi dan Atasan.
2. Memperketat menu dan route Discrepancy agar Dandori tidak memperoleh menu/akses tersebut; Discrepancy tetap tersedia bagi Staf Administrasi, Atasan, Operator Warehouse, Operator Assembly, dan Operator Delivery.
3. Membatasi pembuatan sesi scanner HP pada role pelaksana scan: Operator Warehouse, Dandori, Operator Assembly, dan Operator Delivery.
4. Menyesuaikan tautan Export CSV pada halaman Discrepancy agar hanya ditampilkan bagi Staf Administrasi dan Atasan.
5. Menyembunyikan ringkasan/tautan Mini Cycle Count pada Dashboard Dandori, karena fitur tersebut tidak tercantum pada Use Case Dandori.
6. Memfilter tabel Incoming Warehouse agar hanya menampilkan box yang memiliki movement penerimaan awal (`receiving`), bukan box sisa Dandori atau box FG hasil Assembly.
7. Membuka filter tanggal pada halaman Dandori untuk Staf Administrasi dan Atasan dalam mode baca-saja.
8. Menambah filter jenis movement `delivery_outgoing` dan `delivery_loading` agar cocok dengan tipe movement yang benar-benar dibuat oleh proses Delivery.
9. Memperbaiki perhitungan kolom kebutuhan BOM “Telah disiapkan” agar mencakup persiapan yang masih `prepared` maupun yang sudah `assembled`.
10. Memperketat proses Mini Cycle Count: hasil mismatch yang masih `open` harus ditinjau/revisi Atasan sebelum persetujuan; seluruh detail harus diperiksa saat revisi disimpan. Jika semua detail sejak awal `match`, persetujuan dapat dilakukan tanpa revisi angka.
11. Menyertakan konfigurasi `config/view.php` dan folder runtime storage untuk menghindari compiled view path kosong pada paket hasil ekstraksi.
12. Memperbaiki petunjuk setup: aset CSS/JS paket dilayani langsung dari `public/`, bukan melalui entrypoint Vite.
13. Menyesuaikan script `npm run build` dan `npm run dev` agar tidak lagi menjalankan Vite yang mencari `resources/css/app.css` dan `resources/js/app.js` yang memang tidak digunakan oleh website ini.

## Akses transaksi Staf Administrasi dan Atasan

Pada file `resources/views/transactions/incoming-warehouse.blade.php` di ZIP yang diaudit, form sudah diapit kondisi baca-saja untuk Staf Administrasi dan Atasan. Karena screenshot sebelumnya masih menampilkan form dengan label/teks yang berbeda dari file ini, ada indikasi kuat bahwa folder lokal yang sedang dijalankan bukan salinan ZIP terbaru atau Blade cache masih berasal dari versi sebelumnya. Setelah ekstrak paket ini ke folder baru, jalankan `php artisan optimize:clear`, hentikan server lama, dan jalankan `php artisan serve` dari folder yang baru.

## Pemeriksaan statis yang berhasil

- 50 file PHP aplikasi, route, seeder, dan test lulus `php -l`.
- File JavaScript di `public/js` lulus `node --check`.
- 27 Blade view berhasil dikompilasi langsung melalui Blade compiler; hasil compiled PHP lulus pemeriksaan sintaks.
- Route middleware diverifikasi melalui `php artisan route:list --json`: Riwayat Transaksi, Discrepancy, dan sesi scanner memiliki pembatasan role seperti pada matriks.
- Role middleware diuji manual: role yang diizinkan lolos, dan Dandori ditolak pada route yang hanya mengizinkan Staf Administrasi/Atasan.

## Batas pemeriksaan

Uji integrasi database dan end-to-end masih harus dijalankan di komputer lokal karena koneksi database lokal tidak tersedia di lingkungan audit. Fungsi kamera/scanner HP juga perlu diuji pada laptop dan HP pengguna melalui koneksi HTTPS. Dengan demikian, hasil audit statis bukan klaim bahwa semua transaksi telah lolos uji runtime.

## Ketidaksesuaian sumber yang tidak diubah secara sepihak

1. **Part NG dan NG Area:** TO-BE menyebut produk NG dicatat dan ditempatkan di NG Area. Implementasi saat ini menyimpan pencatatan ke entitas `Part_NG`, tetapi belum membentuk jejak lokasi/box NG. ERD yang diberikan tidak menunjukkan relasi langsung Part_NG ke Material_Box/Material_Movement untuk traceability NG. Perlu keputusan pada rancangan ERD sebelum menambahkan jejak lokasi, agar tidak menciptakan relasi baru di luar acuan.
2. **Discrepancy penerimaan supplier:** AS-IS/TO-BE menyebut pelaporan jika material atau Delivery Note tidak sesuai. Halaman Discrepancy yang tersedia sekarang membandingkan Stock System dengan Stock Actual dari Stock Opname dan Mini Cycle Count, bukan laporan selisih penerimaan supplier. ERD yang diberikan belum menunjukkan entitas khusus untuk discrepancy penerimaan.
3. **Barcode Assembly:** Activity Diagram Operator Assembly menyebut pemindaian barcode HFG/material, sedangkan TO-BE dan Use Case mengarah pada Part Tag/FG. Implementasi memakai Part Tag dari Dandori, mengikuti TO-BE. Wording diagram perlu diselaraskan oleh pemilik rancangan.
4. **Barcode Delivery:** Use Case menyebut barcode produk, sementara TO-BE menyebut Part Tag. Implementasi memakai Part Tag sebagai identitas FG hasil Assembly, mengikuti TO-BE.
5. **Mini Cycle Count:** Use Case Operator Assembly dan Operator Delivery mencantumkan Mini Cycle Count, tetapi Activity Diagram kedua role tidak menggambarkannya. Paket mengikuti Use Case; Activity Diagram perlu dilengkapi bila aktivitas tersebut memang dimaksudkan.
6. **Label swimlane diagram:** Activity Diagram Dashboard Atasan dan Notifikasi Dandori memiliki label aktor yang tidak konsisten sebagaimana dicatat pada dokumen pemetaan revisi.
7. **Stock Opname:** tabel Stock_Opname dan STO_Detail tersedia di ERD/data penelitian, namun perlu dipastikan apakah proses input Stock Opname manual memang merupakan Use Case yang harus tampak sebagai menu tersendiri. Modul dipertahankan karena entitas tersebut ada di ERD dan digunakan untuk ringkasan discrepancy/inventory accuracy.
8. **QR supplier aktual:** QR dalam `sample-supplier-qr` adalah payload demo sintetis. Kesesuaian parser dengan QR pemasok aktual belum dapat divalidasi tanpa payload/hasil scan dari barcode perusahaan.

Poin-poin yang masih terbuka tersebut sengaja tidak “diselesaikan” dengan menambah tabel, kolom, maupun alur baru tanpa persetujuan terhadap rancangan sumber.

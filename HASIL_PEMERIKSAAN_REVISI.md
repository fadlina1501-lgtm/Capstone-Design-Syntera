# Hasil Pemeriksaan Statis Paket Revisi

Tanggal pemeriksaan: 5 November 2026

## Pemeriksaan yang berhasil

- Sintaks 50 file PHP pada `app`, `routes`, `database/seeders`, dan `tests`: lulus (`php -l`).
- Sintaks 5 file JavaScript pada `public/js`: lulus (`node --check`).
- Daftar route Laravel: 51 route terdaftar (`php artisan route:list --except-vendor`).
- Kompilasi langsung 27 Blade view melalui Blade compiler Laravel: berhasil.

## Pemeriksaan yang belum dapat dilakukan di lingkungan ini

- PHPUnit belum dapat dijalankan karena ekstensi PHP `DOM` tidak tersedia (`Class "DOMDocument" not found`).
- Uji integrasi database belum dapat dijalankan karena instalasi PHP pemeriksaan tidak menyediakan driver PDO MySQL/SQLite.
- Build frontend Vite belum dapat diverifikasi pada lingkungan Linux pemeriksaan ini. `npm run build` berhenti pada `vite: Permission denied`; pemanggilan Vite langsung juga memerlukan binding Rolldown khusus platform Linux yang tidak tersedia di paket `node_modules` hasil ekstraksi Windows.
- Karena keterbatasan tersebut, transaksi dengan database dan akses kamera HP tetap harus diuji pada komputer Windows/XAMPP milik pengguna setelah `.env` disetel dan server dijalankan.

## Catatan keamanan dan data

- File `.env` lokal tidak disertakan. Buat `.env` dari `.env.example` dan isi kredensial database lokal.
- Jangan menjalankan `php artisan migrate:fresh` pada database yang berisi data penting.
- Seeder baru menyiapkan akun dan master data demo secara idempotent; tidak membuat box atau stok awal. Material demo harus dimasukkan melalui Incoming Warehouse melalui barcode supplier agar mengikuti TO-BE.
- Backup database sebelum menjalankan `php artisan migrate --seed` pada database yang sudah pernah dipakai.

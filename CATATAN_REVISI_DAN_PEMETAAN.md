# Catatan Revisi dan Pemetaan Rancangan ke Website

Dokumen ini menjelaskan perubahan pada paket website dan titik yang perlu dikonfirmasi pada dokumen rancangan. Tujuannya menjaga keterlacakan antara AS-IS, TO-BE, ERD, Use Case, Activity Diagram, dan implementasi.

## A. Pemetaan fitur

| Proses / Use Case | Implementasi web yang disiapkan | Data utama ERD |
|---|---|---|
| Login dan hak akses | Auth, role-based middleware, menu per role | Role, Users, Audit_Log |
| Incoming Warehouse | Scan QR supplier, autofill form, verifikasi operator, simpan penerimaan | Material_Box, Stock, Material_Movement |
| Incoming Rack | Scan barcode box yang sama, pindahkan saldo ke Incoming Rack | Material_Box, Stock, Material_Movement, Location |
| Production Plan | Daftar rencana produksi dengan filter tanggal | Production_Plan, Part |
| Dandori | Pilih plan/FG, lihat BOM dan stok rack, scan box komponen, buat Part Tag | Bill_of_Material, Material_Box, Stock, Preparation, Preparation_Detail, Material_Movement |
| Assembly | Scan Part Tag, catat qty FG produced, buat identitas box FG | Preparation, Preparation_Detail, Assembly_Process, Material_Box, Stock, Material_Movement |
| Part NG | Form terpisah untuk part, qty, reason, dan plan | Part_NG, Part, Production_Plan, Users |
| Delivery | Scan Part Tag ke Outgoing Area, lalu pindah ke Loading Area | Material_Box, Assembly_Process, Material_Movement, Stock, Location |
| Stock Opname | Staf Administrasi mencatat hasil perbandingan stok sistem dan stok aktual untuk monitoring inventory accuracy | Stock_Opname, STO_Detail, Stock, Part, Users |
| Monitoring / Reports | Stock, movement, discrepancy, daily production dan reports | Stock, Material_Movement, Stock_Opname, STO_Detail, Assembly_Process, Part_NG |

## B. Perubahan penting

1. Memecah menu Assembly dan Part NG menjadi dua halaman transaksi. Hal ini mengikuti Use Case dan Activity Diagram Operator Assembly yang memisahkan pencatatan hasil Assembly dan penambahan Part NG.
2. Menetapkan halaman transaksi untuk Staf Administrasi dan Atasan sebagai baca-saja; transaksi operasional hanya dapat disimpan oleh role pelaksana.
3. Menambah halaman Production Plan untuk Dandori, Operator Assembly, dan Operator Delivery.
4. Menambah pemetaan perpindahan Delivery dua tahap: Assembly ke Outgoing Area dan Outgoing Area ke Loading Area.
5. Mempertahankan modul Stock Opname untuk perbandingan stock system dan stock actual.
6. Membetulkan link cetak label sisa box agar menggunakan route box-tag, bukan route Part Tag persiapan.
7. Menampilkan rincian kebutuhan BOM dan stok Incoming Rack pada halaman Dandori sebelum operator memindai box komponen.
8. Mengubah seeder agar tidak membuat stok/box awal fiktif. Material box dan stok harus tercipta melalui scan supplier pada Incoming Warehouse.
9. Menambahkan lokasi Outgoing Area dan Loading Area pada data demo serta pilihan lokasi master.

## C. Hal yang belum sepenuhnya konsisten pada diagram sumber

1. Activity Diagram Operator Assembly menyebut scanning barcode material HFG, sementara TO-BE dan Use Case Assembly mengarah pada scanning Part Tag/FG setelah Dandori. Implementasi menggunakan Part Tag untuk Assembly, karena Part Tag dihasilkan Dandori dan dibawa menuju Assembly sesuai TO-BE. Perbedaan wording activity diagram perlu dikonfirmasi sebelum laporan difinalisasi.
2. Use Case Operator Assembly dan Operator Delivery mencantumkan Mini Cycle Count, tetapi Activity Diagram Monitoring Persediaan kedua aktor tersebut hanya menggambarkan Stock Monitoring, Stock Movement, dan Discrepancy. Atas keputusan ruang lingkup implementasi terbaru, Mini Cycle Count tidak ditampilkan pada versi deployment ini; tabel legacy tetap dipertahankan agar data lama tidak dihapus.
3. Diagram Activity Dashboard Atasan memiliki label swimlane “Operator Assembly”; label aktor seharusnya diperiksa dan kemungkinan diganti menjadi “Atasan”.
4. Diagram Activity Notifikasi Dandori memiliki label swimlane “Operator Warehouse”; label aktor seharusnya diperiksa dan kemungkinan diganti menjadi “Dandori”.
5. Tabel Use Case Delivery menyebut scanning barcode produk, sedangkan TO-BE menggambarkan scan Part Tag. Implementasi mengikat identitas box Finished Good ke Part Tag yang dihasilkan Dandori dan digunakan pada Assembly/Delivery.

## D. Batasan

- Perubahan ini tidak mengubah ERD atau menambah kolom/tabel. Fungsi baru menggunakan entitas dan atribut yang sudah tercantum pada ERD.
- Data lama dalam database pengguna tidak dihapus atau dimigrasikan otomatis. Seeder lama sebelumnya dapat meninggalkan data demo awal seperti `DEMO-FG-001` atau opening stock pada database yang telah digunakan. Buat backup dan bersihkan data demo lama secara selektif bila diperlukan.
- Prototype tidak mengintegrasikan pengiriman ke sistem eksternal atau mengambil data dari sistem ERP/Nova perusahaan.
- QR pada folder `sample-supplier-qr` adalah payload fiktif uji coba; QR supplier aktual perlu dipetakan melalui hasil decoding aktual.


## E. Pengetatan hak akses dan tampilan (pemeriksaan lanjutan)

- Halaman Riwayat Transaksi keseluruhan telah dihapus dari menu dan route pada versi deployment ini. Monitoring Stock Movement tetap dipertahankan sebagai informasi perpindahan material.
- Stock Monitoring dan Stock Movement tersedia bagi keenam role. Discrepancy tersedia untuk Staf Administrasi, Atasan, Operator Warehouse, Operator Assembly, dan Operator Delivery; menu/route Dandori tidak menampilkan atau mengizinkan Discrepancy.
- Akses pembuatan sesi scanner HP dibatasi pada role operasional yang memiliki transaksi pemindaian: Operator Warehouse, Dandori, Operator Assembly, dan Operator Delivery.
- Menu, route, controller, dan halaman Mini Cycle Count tidak termasuk dalam versi deployment ini. Tabel legacy tidak dihapus dari database.
- Export CSV pada halaman Discrepancy hanya ditampilkan untuk Staf Administrasi dan Atasan, sama dengan pembatasan route Reports.
- Daftar Incoming Warehouse hanya menampilkan box yang memiliki movement awal bertipe `receiving`, tidak mencampur box sisa Dandori atau box FG hasil Assembly.
- Filter tanggal pada halaman Dandori tersedia juga untuk Staf Administrasi dan Atasan dalam mode baca-saja.
- Perubahan route dan tampilan tidak menambah entitas/tabel/kolom database.

## F. Catatan saat menjalankan paket revisi

- Jika browser masih memperlihatkan form transaksi pada akun Staf Administrasi/Atasan yang seharusnya baca-saja, pastikan terminal berada pada folder dari ZIP revisi terbaru lalu jalankan `php artisan optimize:clear` dan restart `php artisan serve`.
- Halaman menggunakan aset statis `public/css` dan `public/js`; `npm run build` tidak diperlukan karena tidak ada entrypoint `resources/css/app.css` atau `resources/js/app.js` pada paket ini.

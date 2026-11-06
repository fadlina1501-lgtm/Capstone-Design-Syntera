# Label QR Supplier Demo

Semua label di folder ini adalah **data fiktif untuk pengujian prototype**.

Untuk uji awal, tampilkan `01_SUP-AST-26018-01.png` di layar laptop atau cetak. Di laptop, buka form Incoming Warehouse lalu pasangkan HP sebagai scanner. Scan QR pada label; seharusnya field Barcode Box, Part, Supplier, Jumlah dalam Box, Nomor PO, Tanggal Penerimaan, Nomor Urut Box, dan Rak/Shelf terisi dari payload QR. Setelah memeriksa, klik Simpan Penerimaan.

Payload ringkas `STK1` di dalam QR dipilih agar muat dalam batas 120 karakter pada antrean scanner. Format contoh: `STK1|B=...|P=...|S=...|Q=...|PO=...|D=...|N=...|R=...`.

QR pada label supplier asli harus diuji dan dipetakan menurut encoding yang benar-benar dikembalikan ketika dipindai. Label ini bukan salinan atau klaim format QR vendor asli.

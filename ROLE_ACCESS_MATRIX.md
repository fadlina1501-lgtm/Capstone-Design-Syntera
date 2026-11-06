# Matriks Hak Akses Website Stock Tracking Line AIJ

Matriks ini mengikuti pembagian enam aktor pada Use Case Diagram dan Activity Diagram yang diberikan.

| Menu / Aktivitas | Staf Administrasi | Operator Warehouse | Dandori | Operator Assembly | Operator Delivery | Atasan |
|---|---|---|---|---|---|---|
| Dashboard | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Master Data | Kelola | — | — | — | — | Lihat saja |
| Incoming Warehouse | Lihat saja | Catat / scan | — | — | — | Lihat saja |
| Incoming Rack | Lihat saja | Catat / scan | — | — | — | Lihat saja |
| Dandori | Lihat saja | — | Catat persiapan / scan / Part Tag | — | — | Lihat saja |
| Assembly | Lihat saja | — | — | Catat Assembly / scan Part Tag | — | Lihat saja |
| Part NG | Lihat saja | — | — | Catat Part NG | — | Lihat saja |
| Delivery | Lihat saja | — | — | — | Catat Delivery / scan / pindah Loading | Lihat saja |
| Stock Monitoring | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Stock Movement | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Discrepancy | Lihat | Lihat | — | Lihat | Lihat | Lihat |
| Stock Opname | Buat / lihat | — | — | — | — | Lihat |
| Production Plan (operasional) | Kelola melalui Master Data | — | Lihat | Lihat | Lihat | Lihat melalui Master Data |
| Daily Production | Lihat | — | — | — | — | Lihat |
| Reports / Export | Lihat / export | — | — | — | — | Lihat / export |
| Notifikasi | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Hubungkan HP untuk scan | — | Boleh | Boleh | Boleh | Boleh | — |

**Makna tanda “—”**: menu/route tidak diberikan pada role tersebut. Untuk halaman transaksi yang bersifat “Lihat saja”, backend tetap menolak request POST role non-operasional; bukan hanya menyembunyikan form.

## Urutan uji proses utama

1. Login `warehouse`: Incoming Warehouse → Incoming Rack.
2. Login `dandori`: Production Plan → Dandori → scan box komponen sesuai BOM → hasilkan Part Tag.
3. Login `assembly`: Production Plan → Assembly dengan Part Tag → Part NG jika ada hasil tidak sesuai.
4. Login `delivery`: Production Plan → Delivery ke Outgoing Area → pindahkan ke Loading Area.
5. Login `atasan`: pantau Stock Monitoring, Stock Movement, Discrepancy, Stock Opname, Daily Production, dan Reports.
6. Login `admin`: kelola Master Data, lihat data transaksi pada halaman proses, monitoring, Daily Production, Stock Opname, dan Reports.

Untuk lokal, akun demo default menggunakan password `Capstone123!`. Pada production, gunakan secret `DEMO_USER_PASSWORD` yang kuat dan jangan menyimpannya di repository.

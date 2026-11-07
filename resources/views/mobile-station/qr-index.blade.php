@extends('layouts.app')
@section('content')
@php($usesLocalHost = preg_match('/localhost|127\.0\.0\.1/i', $baseUrl) === 1)
<div class="page-head">
    <div><h1>QR Stasiun Operator</h1><p>QR permanen per meja untuk membuka halaman transaksi langsung di HP operator.</p></div>
    <button type="button" class="btn" onclick="window.print()">Cetak Semua QR</button>
</div>
<div class="alert info">
    Setiap QR hanya membuka halaman stasiun. Operator tetap harus login menggunakan akun sesuai role. Setelah masuk, pemindaian dan Submit dilakukan dari HP; transaksi langsung dikirim ke sistem, bukan ke laptop.
</div>
@if($usesLocalHost)
    <div class="alert warning" style="background:#fff7e6;border:1px solid #f2d28b;color:#875b13;padding:12px 14px;border-radius:10px;margin-bottom:16px">
        <strong>Jangan cetak QR ini dulu.</strong> Alamat dasar masih localhost/127.0.0.1 dan tidak dapat dibuka dari HP. Isi `SCAN_STATION_BASE_URL` pada `.env` dengan alamat HTTPS stabil yang dapat diakses laptop dan HP, jalankan `php artisan optimize:clear`, lalu buka halaman ini kembali.
    </div>
@endif
<div class="card" style="margin-bottom:18px">
    <div class="field" style="margin-bottom:0"><label>Alamat dasar QR</label><input value="{{ $baseUrl }}" readonly><small>Gunakan alamat HTTPS yang stabil agar QR yang sudah dicetak tetap berlaku. Jika alamat ngrok berubah, QR perlu dibuat dan dicetak ulang.</small></div>
</div>
<div class="station-qr-grid">
    @foreach($stations as $key => $station)
        <article class="station-qr-card">
            <div class="station-qr-copy">
                <span class="station-qr-kicker">QR STASIUN KERJA</span>
                <h2>{{ $station['label'] }}</h2>
                <p>{{ $station['desk'] }}</p>
                <span class="badge info">{{ $station['role'] }}</span>
            </div>
            <div class="station-qr-code" data-station-qr="{{ $key }}" data-qr-url="{{ $baseUrl }}/mobile-station/{{ $key }}">
                <span>Memuat QR…</span>
            </div>
            <code class="station-qr-url">{{ $baseUrl }}/mobile-station/{{ $key }}</code>
        </article>
    @endforeach
</div>
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(() => {
    document.querySelectorAll('[data-station-qr]').forEach((node) => {
        const value = node.dataset.qrUrl;
        node.innerHTML = '';
        if (window.QRCode) {
            new QRCode(node, { text: value, width: 176, height: 176, correctLevel: QRCode.CorrectLevel.M });
        } else {
            node.textContent = 'Library QR tidak termuat. Periksa koneksi internet lalu refresh halaman.';
        }
    });
})();
</script>
@endpush
<style>
.station-qr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.station-qr-card{background:#fff;border:1px solid #dce5f2;border-radius:16px;padding:20px;display:grid;grid-template-columns:minmax(0,1fr) 190px;gap:12px;align-items:center;break-inside:avoid}.station-qr-kicker{font-size:10px;font-weight:900;letter-spacing:1.4px;color:#4264a8}.station-qr-copy h2{font-size:20px;margin:7px 0}.station-qr-copy p{font-size:12px;color:#62748e;margin:0 0 12px}.station-qr-code{width:190px;height:190px;display:grid;place-items:center;background:#fff;border:1px solid #e3eaf4;border-radius:12px;padding:7px}.station-qr-code img{max-width:100%;height:auto}.station-qr-url{grid-column:1/-1;font-size:10px;overflow-wrap:anywhere;color:#52647e;background:#f5f7fb;padding:7px;border-radius:7px}@media(max-width:850px){.station-qr-grid{grid-template-columns:1fr}}@media(max-width:530px){.station-qr-card{grid-template-columns:1fr}.station-qr-code{margin:auto}}@media print{.sidebar,.topbar,.page-head .btn,.alert,.card{display:none!important}main{padding:0!important}.station-qr-grid{grid-template-columns:repeat(2,1fr);gap:10px}.station-qr-card{border:1px solid #888;box-shadow:none;border-radius:8px;padding:12px;grid-template-columns:1fr 145px}.station-qr-code{width:145px;height:145px}.station-qr-copy h2{font-size:16px}.station-qr-url{font-size:8px}}
</style>
@endsection

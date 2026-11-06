<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner HP · Stock Tracking</title>
    <style>
        :root{color-scheme:light;--navy:#152747;--blue:#2458c6;--pale:#eef4ff;--line:#dce6f5;--muted:#60718b;--green:#067647}
        *{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#172944;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;min-height:100vh}
        .app{max-width:560px;margin:0 auto;padding:20px 16px 34px}.brand{display:flex;align-items:center;gap:11px;margin:8px 0 26px}.brandmark{width:44px;height:44px;border-radius:13px;background:var(--blue);color:white;display:grid;place-items:center;font-weight:900;letter-spacing:-.5px}.brand strong{display:block;font-size:16px}.brand small{display:block;color:var(--muted);font-size:12px;margin-top:2px}
        .card{background:white;border:1px solid var(--line);border-radius:22px;padding:20px;box-shadow:0 14px 40px rgba(24,49,88,.07)}.eyebrow{font-size:10px;font-weight:900;letter-spacing:1.7px;text-transform:uppercase;color:var(--blue)}h1{margin:8px 0;font-size:25px;line-height:1.18;letter-spacing:-.8px}.intro{margin:0;color:var(--muted);font-size:14px;line-height:1.55}.mode{display:inline-flex;border-radius:999px;background:var(--pale);color:#244fa7;padding:7px 10px;font-size:11px;font-weight:800;margin-top:14px}
        #reader{width:100%;min-height:220px;margin-top:18px;border-radius:16px;overflow:hidden;background:#101b2e}#reader video{width:100%!important;object-fit:cover;border-radius:16px}#reader__dashboard_section_csr button,#reader button{border:0!important;border-radius:10px!important;padding:10px 12px!important;background:var(--pale)!important;color:#194aa8!important;font-weight:800!important}
        .actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}.btn{min-height:48px;border-radius:12px;border:1px solid var(--line);padding:12px 14px;font-weight:800;font-size:14px;cursor:pointer;touch-action:manipulation;background:white;color:var(--navy)}.btn.primary{background:var(--blue);border-color:var(--blue);color:white}.btn.submit{width:100%;margin-top:12px;background:#087443;border-color:#087443;color:white;min-height:54px;font-size:15px}.btn:disabled{opacity:.48;cursor:not-allowed}.status{margin:15px 0 0;padding:12px 13px;background:#f1f5fb;border-radius:12px;color:#4c607d;font-size:13px;line-height:1.5;overflow-wrap:anywhere}.status.success{background:#e8f8ef;color:#067647}.status.error{background:#fff0ef;color:#b42318}.status.pending{background:#fff8e6;color:#915d00}.scan-count{margin:12px 0 0;color:var(--muted);font-size:12px}.foot{margin:16px 4px 0;color:#78879c;font-size:11px;line-height:1.5;text-align:center}
        @media(max-width:380px){.app{padding:13px 10px 24px}.card{padding:15px}h1{font-size:22px}.actions{grid-template-columns:1fr}}
    </style>
    <script src="/js/html5-qrcode.min.js?v=3"></script>
</head>
<body>
<main class="app">
    <div class="brand"><div class="brandmark">ST</div><div><strong>Stock Tracking</strong><small>Line AIJ · Scanner HP</small></div></div>
    <section class="card">
        <div class="eyebrow">Mode scanner</div>
        <h1>Pindai barcode material</h1>
        <p class="intro">Hasil scan dikirim ke form transaksi di laptop. Periksa data pada laptop, lalu tekan tombol <strong>Submit Transaksi</strong> di HP ini untuk mengirim form yang sudah diperiksa.</p>
        <span class="mode">{{ $mode === 'multiple' ? 'Scan beberapa box · Dandori' : 'Scan satu barcode' }}</span>
        <div id="reader" aria-label="Area kamera pemindai barcode"></div>
        <div class="actions">
            <button class="btn primary" type="button" id="start-camera">Mulai Kamera</button>
            <button class="btn" type="button" id="stop-camera" disabled>Hentikan Kamera</button>
        </div>
        <p class="scan-count" id="scan-count">Barcode terkirim: 0</p>
        <button class="btn submit" type="button" id="submit-transaction" disabled>Submit Transaksi dari HP</button>
        <p class="status" id="scan-status" aria-live="polite">Tekan “Mulai Kamera”, lalu izinkan akses kamera belakang HP.</p>
    </section>
    <p class="foot">HP digunakan untuk scan dan mengirim perintah submit. Periksa kembali data transaksi di laptop sebelum menekan Submit. Sesi berakhir otomatis setelah 15 menit.</p>
</main>
<script>
(() => {
    'use strict';
    const mode = @json($mode);
    const scanUrl = @json(route('phone-scanner.scan', ['token' => $token], false));
    const submitUrl = @json(route('phone-scanner.submit', ['token' => $token], false));
    const statusUrl = @json(route('phone-scanner.status', ['token' => $token], false));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const statusLine = document.getElementById('scan-status');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const submitButton = document.getElementById('submit-transaction');
    const scanCount = document.getElementById('scan-count');
    let reader = null;
    let sending = false;
    let lastValue = '';
    let lastAt = 0;
    let stopped = false;
    let scannedCount = 0;
    let submitPending = false;
    let statusTimer = null;

    function status(message, type) {
        statusLine.textContent = message;
        statusLine.classList.toggle('success', type === 'success');
        statusLine.classList.toggle('error', type === 'error');
        statusLine.classList.toggle('pending', type === 'pending');
    }
    function updateCount() {
        scanCount.textContent = 'Barcode terkirim: ' + scannedCount;
        submitButton.disabled = scannedCount < 1 || submitPending;
    }
    function formats() {
        const f = window.Html5QrcodeSupportedFormats;
        return [f.CODE_39, f.CODE_128, f.EAN_13, f.EAN_8, f.ITF, f.QR_CODE].filter((x) => x !== undefined);
    }
    async function startCamera() {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            status('Kamera membutuhkan URL HTTPS yang aman. Buka link scanner HTTPS dari QR di laptop.', 'error');
            return;
        }
        if (!window.Html5Qrcode) {
            status('Library scanner belum termuat. Periksa koneksi internet, lalu refresh halaman.', 'error');
            return;
        }
        try {
            stopped = false;
            startButton.disabled = true;
            status('Meminta izin kamera…');
            reader = new Html5Qrcode('reader', { formatsToSupport: formats(), verbose: false });
            await reader.start({ facingMode: 'environment' }, {
                fps: 10,
                aspectRatio: 1.0,
                formatsToSupport: formats(),
                qrbox: (width, height) => ({ width: Math.max(180, Math.min(330, Math.floor(width * .82))), height: Math.max(90, Math.min(170, Math.floor(height * .36))) }),
            }, onDecoded, () => {});
            stopButton.disabled = false;
            status('Kamera aktif. Arahkan kamera ke barcode pada label box.', 'success');
        } catch (e) {
            startButton.disabled = false;
            status((e?.message || 'Kamera tidak dapat dibuka.') + ' Pastikan izin kamera diberikan ke browser.', 'error');
            await stopCamera(false);
        }
    }
    async function onDecoded(text) {
        const value = String(text || '').trim();
        const now = Date.now();
        if (!value || sending || submitPending || (value === lastValue && now - lastAt < 1700)) return;
        sending = true;
        lastValue = value;
        lastAt = now;
        status('Barcode terbaca: ' + value + '. Mengirim ke laptop…', 'pending');
        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ barcode: value }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Barcode gagal dikirim. Sesi mungkin sudah berakhir.');
            scannedCount = Math.max(scannedCount + 1, Number(data.sequence || 0));
            updateCount();
            status('✓ ' + value + ' berhasil dikirim ke laptop. Periksa data transaksi di laptop, kemudian tekan Submit Transaksi di HP.', 'success');
            if (mode === 'single' || data.status === 'completed') {
                await stopCamera(false);
                stopped = true;
                if (mode === 'single') startButton.disabled = true;
            }
        } catch (e) {
            status(e?.message || 'Barcode gagal dikirim ke laptop.', 'error');
        } finally {
            sending = false;
            if (!stopped && mode === 'single') startButton.disabled = false;
        }
    }
    async function stopCamera(showStatus = true) {
        if (reader) {
            try { if (reader.isScanning) await reader.stop(); } catch (_) {}
            try { await reader.clear(); } catch (_) {}
            reader = null;
        }
        stopButton.disabled = true;
        startButton.disabled = false;
        if (showStatus) status('Kamera dihentikan. Barcode yang sudah dikirim tetap tersimpan di form laptop.');
    }
    async function requestSubmit() {
        if (scannedCount < 1 || submitPending) return;
        submitPending = true;
        updateCount();
        await stopCamera(false);
        status('Mengirim permintaan Submit ke laptop…', 'pending');
        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Permintaan Submit gagal dikirim.');
            status(data.message || 'Permintaan Submit diterima laptop. Menunggu pemeriksaan form…', 'pending');
            startStatusPolling();
        } catch (e) {
            submitPending = false;
            updateCount();
            status(e?.message || 'Permintaan Submit gagal. Coba lagi.', 'error');
        }
    }
    function startStatusPolling() {
        if (statusTimer) window.clearInterval(statusTimer);
        let attempts = 0;
        statusTimer = window.setInterval(async () => {
            attempts += 1;
            try {
                const response = await fetch(statusUrl, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || 'Status sesi tidak dapat diperiksa.');
                if (data.submit_result === 'validation_error') {
                    window.clearInterval(statusTimer);
                    statusTimer = null;
                    submitPending = false;
                    updateCount();
                    status(data.submit_message || 'Form belum lengkap. Lengkapi data di laptop, lalu tekan Submit dari HP lagi.', 'error');
                } else if (data.submit_result === 'dispatched' || data.status === 'completed') {
                    window.clearInterval(statusTimer);
                    statusTimer = null;
                    status('✓ Perintah Submit dari HP sudah diteruskan ke laptop. Periksa hasil penyimpanan pada halaman laptop.', 'success');
                    submitButton.disabled = true;
                } else if (!data.active) {
                    window.clearInterval(statusTimer);
                    statusTimer = null;
                    submitPending = false;
                    updateCount();
                    status('Sesi pairing sudah berakhir. Hubungkan ulang dari laptop.', 'error');
                } else if (attempts >= 35) {
                    window.clearInterval(statusTimer);
                    statusTimer = null;
                    submitPending = false;
                    updateCount();
                    status('Laptop belum merespons. Pastikan halaman transaksi di laptop masih terbuka dan koneksi ngrok aktif, lalu coba Submit lagi.', 'error');
                }
            } catch (e) {
                window.clearInterval(statusTimer);
                statusTimer = null;
                submitPending = false;
                updateCount();
                status(e?.message || 'Gagal memeriksa status Submit.', 'error');
            }
        }, 700);
    }

    startButton.addEventListener('click', startCamera);
    stopButton.addEventListener('click', () => { stopped = true; stopCamera(true); });
    submitButton.addEventListener('click', requestSubmit);
    async function loadExistingSessionState() {
        try {
            const response = await fetch(statusUrl, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) return;
            scannedCount = Number(data.sequence || 0);
            updateCount();
            if (mode === 'single' && scannedCount > 0) { startButton.disabled = true; stopButton.disabled = true; }
            if (data.submit_result === 'pending') {
                submitPending = true;
                updateCount();
                status('Permintaan Submit sedang menunggu laptop memeriksa form…', 'pending');
                startStatusPolling();
            } else if (data.submit_result === 'validation_error') {
                status(data.submit_message || 'Form perlu dilengkapi pada laptop sebelum Submit ulang dari HP.', 'error');
            } else if (data.submit_result === 'dispatched' || data.status === 'completed') {
                submitButton.disabled = true;
                status('Perintah Submit sudah diteruskan ke laptop. Periksa hasil transaksi pada halaman laptop.', 'success');
            } else if (scannedCount > 0) {
                status('Barcode sudah diterima laptop. Periksa data transaksi di laptop, kemudian tekan Submit Transaksi di HP.', 'success');
            }
        } catch (_) { /* The scanner can still be started; status will be refreshed when used. */ }
    }
    window.addEventListener('pagehide', () => { if (statusTimer) window.clearInterval(statusTimer); });
    updateCount();
    loadExistingSessionState();
})();
</script>
</body>
</html>

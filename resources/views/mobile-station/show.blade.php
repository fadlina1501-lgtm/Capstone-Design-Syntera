<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $station['label'] }} · Stock Tracking</title>
    <style>
        :root{color-scheme:light;--navy:#172b4d;--blue:#315fe8;--pale:#edf3ff;--line:#dce5f2;--muted:#64748b;--green:#087443}
        *{box-sizing:border-box}body{margin:0;background:#f2f5fa;color:var(--navy);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;min-height:100vh}.app{max-width:680px;margin:0 auto;padding:16px 14px 34px}.brand{display:flex;align-items:center;gap:10px;margin:5px 3px 18px}.brandmark{width:42px;height:42px;border-radius:13px;background:var(--blue);color:#fff;display:grid;place-items:center;font-weight:900}.brand strong{display:block;font-size:15px}.brand small{display:block;color:var(--muted);font-size:11px;margin-top:2px}.brand form{margin-left:auto}.logout{border:1px solid var(--line);background:#fff;color:#44546a;border-radius:9px;padding:8px 10px;font-size:11px;font-weight:800}.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px;margin-bottom:13px;box-shadow:0 10px 28px rgba(24,49,88,.05)}.eyebrow{font-size:10px;font-weight:900;letter-spacing:1.5px;text-transform:uppercase;color:var(--blue)}h1{font-size:24px;line-height:1.2;letter-spacing:-.6px;margin:7px 0}.intro{font-size:13px;line-height:1.55;color:var(--muted);margin:0}.role{display:inline-block;margin-top:12px;padding:6px 9px;background:var(--pale);color:#244da8;border-radius:999px;font-size:11px;font-weight:800}#reader{width:100%;min-height:210px;margin:15px 0 10px;border-radius:13px;overflow:hidden;background:#101b2e}#reader video{width:100%!important;object-fit:cover;border-radius:13px}#reader__dashboard_section_csr button,#reader button{border:0!important;border-radius:9px!important;padding:9px 11px!important;background:var(--pale)!important;color:#194aa8!important;font-weight:800!important}.camera-actions{display:grid;grid-template-columns:1fr 1fr;gap:9px}.btn{display:inline-flex;justify-content:center;align-items:center;width:100%;min-height:46px;border:1px solid var(--line);border-radius:11px;background:#fff;color:var(--navy);font-size:14px;font-weight:800;padding:11px 13px;text-decoration:none;cursor:pointer;touch-action:manipulation}.btn.primary{background:var(--blue);border-color:var(--blue);color:white}.btn:disabled{opacity:.5;cursor:not-allowed}.status{padding:11px 12px;border-radius:10px;background:#f1f5fb;color:#50627c;font-size:12px;line-height:1.45;margin:12px 0 0;overflow-wrap:anywhere}.status.success{background:#e7f7ed;color:var(--green)}.status.error{background:#fff0ef;color:#b42318}.field{margin:12px 0}.field label{display:block;font-size:12px;font-weight:800;margin:0 0 6px}.field input,.field select,.field textarea{display:block;width:100%;min-height:46px;border:1px solid #cfd9e8;border-radius:10px;padding:11px 12px;background:white;color:#172b4d;font:inherit;font-size:15px}.field textarea{min-height:112px;resize:vertical}.field small{display:block;color:var(--muted);font-size:11px;line-height:1.45;margin-top:5px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}.form-actions{margin-top:16px}.form-actions .btn{min-height:52px;font-size:15px}.scan-readout{font-size:11px;color:var(--muted);margin-top:7px}.info{padding:11px 12px;border-radius:10px;background:#eff6ff;border:1px solid #d5e5ff;color:#36547f;font-size:12px;line-height:1.5;margin:12px 0}.alert{padding:11px 12px;border-radius:10px;margin:0 0 12px;font-size:13px;line-height:1.5}.alert.success{background:#e7f7ed;color:#087443}.alert.error{background:#fff0ef;color:#b42318}.alert ul{margin:6px 0 0;padding-left:18px}.bom-summary{background:#f7f9fc;border:1px solid var(--line);border-radius:11px;padding:12px;margin-top:12px;font-size:12px}.bom-summary table{width:100%;border-collapse:collapse;margin-top:9px}.bom-summary th,.bom-summary td{text-align:left;padding:7px 4px;border-bottom:1px solid #e4eaf2;font-size:11px}.foot{text-align:center;color:#7b8799;font-size:10px;line-height:1.5;margin:13px 8px}.link-button{display:inline-block;margin-top:9px;color:#2458c6;font-size:13px;font-weight:800}.radio-row{display:flex;gap:8px}.radio-option{flex:1;border:1px solid var(--line);border-radius:10px;padding:10px;font-size:12px;font-weight:800;display:flex;gap:7px;align-items:center}.radio-option input{width:auto;min-height:0}.hidden{display:none!important}
        @media(max-width:420px){.app{padding:12px 10px 26px}.card{padding:15px}h1{font-size:22px}.form-grid{grid-template-columns:1fr}.camera-actions{grid-template-columns:1fr 1fr}}
    </style>
    <script src="/js/html5-qrcode.min.js?v=4"></script>
    <script src="/js/supplier-qr-autofill.js?v=4" defer></script>
</head>
<body>
<main class="app">
    <div class="brand"><div class="brandmark">ST</div><div><strong>Stock Tracking</strong><small>Line AIJ · Transaksi langsung dari HP</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">Keluar</button></form></div>

    @if(session('status'))<div class="alert success">{{ session('status') }}@if(session('mobile_tag_url'))<br><a class="link-button" href="{{ session('mobile_tag_url') }}" target="_blank" rel="noopener">Buka / Cetak Part Tag</a>@endif</div>@endif
    @if($errors->any())<div class="alert error"><strong>Transaksi belum tersimpan:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="card">
        <div class="eyebrow">Stasiun kerja aktif</div>
        <h1>{{ $station['label'] }}</h1>
        <p class="intro">{{ $station['description'] }}</p>
        <span class="role">{{ $station['role'] }} · {{ auth()->user()->full_name }}</span>

        @if($station['form'] !== 'part-ng')
            <div id="reader" aria-label="Area kamera untuk scan barcode"></div>
            <div class="camera-actions">
                <button class="btn primary" type="button" id="start-camera">Mulai Scan</button>
                <button class="btn" type="button" id="stop-camera" disabled>Hentikan Kamera</button>
            </div>
            <p class="status" id="scan-status" aria-live="polite">Tekan Mulai Scan, izinkan kamera belakang, lalu arahkan ke barcode produk/material.</p>
        @else
            <div class="info">Pencatatan Part NG tidak memerlukan scan barcode pada alur saat ini. Isi identitas part, jumlah, dan alasan NG, lalu Submit dari HP.</div>
        @endif
    </section>

    <form id="mobile-transaction-form" class="card" method="POST" action="{{ route($station['post_route'], [], false) }}">
        @csrf
        <input type="hidden" name="_mobile_station" value="{{ $stationKey }}">
        <div class="eyebrow">Form transaksi</div>
        @switch($station['form'])
            @case('incoming-warehouse')
                <div class="field"><label>Barcode box supplier *</label><input class="scan-input" data-scan-target name="box_barcode" value="{{ old('box_barcode') }}" required placeholder="Scan QR/barcode supplier"></div>
                <div class="form-grid">
                    <div class="field"><label>Part *</label><select name="part_id" required><option value="">Pilih part</option>@foreach($parts as $part)<option value="{{ $part->part_id }}" @selected(old('part_id')==$part->part_id)>{{ $part->part_number }} — {{ $part->part_name }}</option>@endforeach</select></div>
                    <div class="field"><label>Supplier</label><select name="supplier_id"><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id')==$supplier->supplier_id)>{{ $supplier->supplier_code }} — {{ $supplier->supplier_name }}</option>@endforeach</select></div>
                    <div class="field"><label>Jumlah dalam box *</label><input type="number" name="qty_in_box" min="0.001" step="0.001" value="{{ old('qty_in_box',1) }}" required></div>
                    <div class="field"><label>Nomor PO</label><input name="po_number" value="{{ old('po_number') }}"></div>
                    <div class="field"><label>Tanggal pengiriman / penerimaan</label><input type="date" name="delivery_date" value="{{ old('delivery_date',now()->toDateString()) }}"></div>
                    <div class="field"><label>Nomor urut box</label><input type="number" name="box_sequence" min="1" value="{{ old('box_sequence',1) }}"></div>
                    <div class="field"><label>Rak / shelf</label><input name="shelf_no" value="{{ old('shelf_no') }}" placeholder="Contoh: D1-1"></div>
                </div>
                <p class="info">QR supplier dapat mengisi barcode, part, supplier, jumlah, PO, tanggal, urutan box, dan shelf. Periksa hasilnya sebelum Submit.</p>
                @break
            @case('incoming-rack')
                <div class="field"><label>Barcode box supplier *</label><input class="scan-input" data-scan-target name="box_barcode" value="{{ old('box_barcode') }}" required placeholder="Scan barcode box dari Incoming Warehouse"></div>
                @break
            @case('dandori')
                <div class="field"><label>Production Plan / Finished Good *</label><select name="plan_id" id="mobile-plan-id" required><option value="">Pilih Production Plan</option>@foreach($plans as $plan)<option value="{{ $plan->plan_id }}" @selected(old('plan_id')==$plan->plan_id)>#{{ $plan->plan_id }} · {{ $plan->finishedGood?->part_number }} — {{ $plan->finishedGood?->part_name }} · {{ $plan->plan_date }} · target {{ number_format((float)$plan->qty_plan,3) }}</option>@endforeach</select></div>
                <div class="field"><label>Jumlah FG yang disiapkan *</label><input name="qty_fg" id="mobile-qty-fg" type="number" min="0.001" step="0.001" value="{{ old('qty_fg',1) }}" required></div>
                <div class="bom-summary" id="mobile-bom-summary">Pilih Production Plan untuk melihat kebutuhan komponen berdasarkan BOM dan stok Incoming Rack.</div>
                <div class="field"><label>Barcode box komponen *</label><textarea class="scan-input" data-scan-target name="barcodes" rows="5" required placeholder="Scan satu barcode box per baris">{{ old('barcodes') }}</textarea><small>Scan setiap box supplier yang digunakan. Satu barcode per baris.</small><div class="scan-readout" id="scan-count">0 box dipindai</div></div>
                @break
            @case('assembly')
                <div class="field"><label>Part Tag / Tag Code *</label><input class="scan-input" data-scan-target name="tag_code" value="{{ old('tag_code') }}" required placeholder="Scan Part Tag hasil Dandori"></div>
                <div class="field"><label>Qty Finished Good yang dihasilkan *</label><input name="qty_produced" type="number" min="0.001" step="0.001" value="{{ old('qty_produced',1) }}" required></div>
                @break
            @case('part-ng')
                <div class="field"><label>Part / Material *</label><select name="part_id" required><option value="">Pilih part</option>@foreach($parts as $part)<option value="{{ $part->part_id }}" @selected(old('part_id')==$part->part_id)>{{ $part->part_number }} — {{ $part->part_name }}</option>@endforeach</select></div>
                <div class="field"><label>Production Plan (opsional)</label><select name="plan_id"><option value="">Tidak dipilih</option>@foreach($plans as $plan)<option value="{{ $plan->plan_id }}" @selected(old('plan_id')==$plan->plan_id)>#{{ $plan->plan_id }} · {{ $plan->finishedGood?->part_number }} · {{ $plan->plan_date }}</option>@endforeach</select></div>
                <div class="field"><label>Jumlah Part NG *</label><input type="number" name="qty_ng" min="0.001" step="0.001" value="{{ old('qty_ng',1) }}" required></div>
                <div class="field"><label>Alasan Part NG *</label><textarea name="ng_reason" rows="3" maxlength="255" required placeholder="Contoh: cacat dimensi, visual, atau fungsi">{{ old('ng_reason') }}</textarea></div>
                @break
            @case('delivery')
                <div class="field"><label>Tahap perpindahan *</label><div class="radio-row"><label class="radio-option"><input type="radio" name="delivery_step" value="outgoing" checked> Assembly → Outgoing</label><label class="radio-option"><input type="radio" name="delivery_step" value="loading"> Outgoing → Loading</label></div></div>
                <div class="field" id="delivery-plan-field"><label>Tanggal Production Plan *</label><select name="plan_date" id="delivery-plan-date"><option value="">Pilih tanggal produksi</option>@foreach($planDates as $date)<option value="{{ \Illuminate\Support\Carbon::parse($date)->format('Y-m-d') }}">{{ \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') }}</option>@endforeach</select></div>
                <div class="field"><label>Part Tag / Barcode FG *</label><input class="scan-input" data-scan-target name="box_barcode" value="{{ old('box_barcode') }}" required placeholder="Scan Part Tag Finished Good"></div>
                @break
        @endswitch
        <div class="form-actions"><button class="btn primary" type="submit" id="submit-transaction">Submit Transaksi dari HP</button></div>
    </form>

    <p class="foot">Transaksi dikirim langsung dari HP ke server Stock Tracking. Tidak perlu menekan Submit di laptop. Gunakan koneksi HTTPS yang sama dengan alamat QR stasiun.</p>
</main>
<script>
(() => {
    'use strict';
    const stationForm = @json($station['form']);
    const stationKey = @json($stationKey);
    const form = document.getElementById('mobile-transaction-form');
    const statusLine = document.getElementById('scan-status');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const submitButton = document.getElementById('submit-transaction');
    let reader = null;
    let scanning = false;
    let lastValue = '';
    let lastAt = 0;

    function status(message, type) {
        if (!statusLine) return;
        statusLine.textContent = message;
        statusLine.classList.toggle('success', type === 'success');
        statusLine.classList.toggle('error', type === 'error');
    }
    function refreshScanCount() {
        const area = form.querySelector('[name="barcodes"]');
        const target = document.getElementById('scan-count');
        if (area && target) target.textContent = area.value.split(/\r?\n/).map(v => v.trim()).filter(Boolean).length + ' box dipindai';
    }
    function setScannedValue(raw) {
        const target = form.querySelector('[data-scan-target]');
        if (!target) return false;
        if (target instanceof HTMLTextAreaElement) {
            const lines = target.value.split(/\r?\n/).map(line => line.trim()).filter(Boolean);
            if (!lines.includes(raw)) lines.push(raw);
            target.value = lines.join('\n');
        } else {
            target.value = raw;
        }
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
        refreshScanCount();
        return true;
    }
    function formats() {
        const f = window.Html5QrcodeSupportedFormats;
        return [f.CODE_39, f.CODE_128, f.EAN_13, f.EAN_8, f.ITF, f.QR_CODE].filter(v => v !== undefined);
    }
    async function onDecoded(text) {
        const value = String(text || '').trim();
        const now = Date.now();
        if (!value || value === lastValue && now - lastAt < 1800) return;
        lastValue = value; lastAt = now;
        if (setScannedValue(value)) {
            status('Barcode terbaca: ' + value + '. Periksa data transaksi, lalu tekan Submit Transaksi dari HP.', 'success');
            if (stationForm === 'dandori') refreshScanCount();
        }
    }
    async function startCamera() {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            status('Kamera membutuhkan HTTPS. Buka QR stasiun melalui alamat HTTPS yang stabil.', 'error'); return;
        }
        if (!window.Html5Qrcode) { status('Library kamera belum termuat. Refresh halaman dan coba kembali.', 'error'); return; }
        try {
            startButton.disabled = true;
            status('Meminta izin kamera HP…');
            reader = new Html5Qrcode('reader', { formatsToSupport: formats(), verbose: false });
            await reader.start({ facingMode: 'environment' }, {
                fps: 10, aspectRatio: 1.0, formatsToSupport: formats(),
                qrbox: (width, height) => ({ width: Math.max(180, Math.min(330, Math.floor(width * .82))), height: Math.max(90, Math.min(170, Math.floor(height * .36))) }),
            }, onDecoded, () => {});
            scanning = true; stopButton.disabled = false;
            status('Kamera aktif. Arahkan ke barcode material atau Part Tag.', 'success');
        } catch (error) {
            startButton.disabled = false;
            status((error?.message || 'Kamera gagal dibuka.') + ' Periksa izin kamera browser.', 'error');
            await stopCamera(false);
        }
    }
    async function stopCamera(show = true) {
        if (reader) {
            try { if (reader.isScanning) await reader.stop(); } catch (_) {}
            try { await reader.clear(); } catch (_) {}
            reader = null;
        }
        scanning = false;
        if (stopButton) stopButton.disabled = true;
        if (startButton) startButton.disabled = false;
        if (show) status('Kamera dihentikan. Tekan Mulai Scan untuk melanjutkan.');
    }
    if (startButton) startButton.addEventListener('click', startCamera);
    if (stopButton) stopButton.addEventListener('click', () => stopCamera(true));
    form?.querySelector('[name="barcodes"]')?.addEventListener('input', refreshScanCount);
    refreshScanCount();

    const deliveryStep = form?.querySelectorAll('[name="delivery_step"]');
    const planField = document.getElementById('delivery-plan-field');
    const planSelect = document.getElementById('delivery-plan-date');
    function updateDeliveryStep() {
        if (stationForm !== 'delivery') return;
        const step = form.querySelector('[name="delivery_step"]:checked')?.value || 'outgoing';
        if (step === 'loading') {
            form.action = @json(route('transactions.delivery.loading', [], false));
            planField?.classList.add('hidden');
            if (planSelect) planSelect.required = false;
        } else {
            form.action = @json(route('transactions.delivery.store', [], false));
            planField?.classList.remove('hidden');
            if (planSelect) planSelect.required = true;
        }
    }
    deliveryStep?.forEach(radio => radio.addEventListener('change', updateDeliveryStep));
    updateDeliveryStep();

    const planSummary = @json($planSummary);
    const planSelectMobile = document.getElementById('mobile-plan-id');
    const qtyFg = document.getElementById('mobile-qty-fg');
    const bomSummary = document.getElementById('mobile-bom-summary');
    const fmt = value => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    function renderBom() {
        if (!planSelectMobile || !bomSummary) return;
        const plan = planSummary[planSelectMobile.value];
        if (!plan) { bomSummary.textContent = 'Pilih Production Plan untuk melihat kebutuhan komponen berdasarkan BOM dan stok Incoming Rack.'; return; }
        const qty = Math.max(0, Number(qtyFg?.value || 0));
        const rows = plan.components.map(component => `<tr><td>${component.part_number} · ${component.part_name}</td><td>${fmt(component.qty_per_unit * qty)}</td><td>${fmt(component.rack_stock)}</td></tr>`).join('');
        bomSummary.innerHTML = `<strong>Kebutuhan BOM untuk ${fmt(qty)} FG</strong><div style="color:#64748b;margin-top:4px">Target plan ${fmt(plan.target)} FG · Sudah disiapkan ${fmt(plan.fg_prepared)} FG</div><table><thead><tr><th>Komponen</th><th>Dibutuhkan</th><th>Stok Rack</th></tr></thead><tbody>${rows || '<tr><td colspan="3">BOM belum tersedia.</td></tr>'}</tbody></table>`;
    }
    planSelectMobile?.addEventListener('change', renderBom);
    qtyFg?.addEventListener('input', renderBom);
    renderBom();

    function resetAfterSuccess() {
        const clear = name => { const field = form.querySelector(`[name=\"${name}\"]`); if (field) field.value = ''; };
        if (stationForm === 'incoming-warehouse') {
            clear('box_barcode');
            clear('part_id'); clear('supplier_id'); clear('po_number'); clear('shelf_no');
            const qty = form.querySelector('[name=\"qty_in_box\"]'); if (qty) qty.value = '1';
            const sequence = form.querySelector('[name=\"box_sequence\"]'); if (sequence) sequence.value = '1';
            const date = form.querySelector('[name=\"delivery_date\"]'); if (date) date.value = new Date().toISOString().slice(0,10);
        } else if (stationForm === 'incoming-rack' || stationForm === 'assembly' || stationForm === 'delivery') {
            clear(stationForm === 'assembly' ? 'tag_code' : 'box_barcode');
        } else if (stationForm === 'dandori') {
            clear('barcodes'); refreshScanCount();
        } else if (stationForm === 'part-ng') {
            clear('part_id'); clear('plan_id'); clear('ng_reason');
            const qty = form.querySelector('[name=\"qty_ng\"]'); if (qty) qty.value = '1';
        }
    }

    function showTagLink(url) {
        const old = form.querySelector('[data-mobile-tag-link]');
        if (old) old.remove();
        if (!url) return;
        const link = document.createElement('a');
        link.href = url; link.target = '_blank'; link.rel = 'noopener';
        link.className = 'link-button'; link.dataset.mobileTagLink = 'true';
        link.textContent = 'Buka / Cetak Part Tag';
        form.querySelector('.form-actions')?.appendChild(link);
    }

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitButton) { submitButton.disabled = true; submitButton.textContent = 'Mengirim transaksi…'; }
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const messages = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(messages.join(' ') || data.message || 'Transaksi gagal dikirim. Periksa koneksi dan data yang diisi.');
            }
            if (!data.ok) throw new Error(data.message || 'Server belum mengonfirmasi transaksi.');
            status(data.message || 'Transaksi berhasil disimpan.', 'success');
            showTagLink(data.mobile_tag_url);
            resetAfterSuccess();
        } catch (error) {
            status(error?.message || 'Transaksi gagal dikirim. Coba kembali.', 'error');
        } finally {
            if (submitButton) { submitButton.disabled = false; submitButton.textContent = 'Submit Transaksi dari HP'; }
        }
    });
})();
</script>
</body>
</html>

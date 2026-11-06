@extends('layouts.app')
@section('content')
@php($readOnly = in_array(auth()->user()->role?->role_name, ['Staf Administrasi','Atasan'], true))
<div class="page-head"><div><h1>Dandori · Persiapan Material</h1><p>Persiapan komponen HFG berdasarkan Production Plan dan Bill of Material (BOM).</p></div></div>
<div class="card"><form method="GET" action="{{ route('transactions.dandori') }}" class="form-grid" style="align-items:end"><div class="field"><label>Tanggal produksi</label><select name="plan_date"><option value="">Semua tanggal</option>@foreach($planDates as $date)<option value="{{ \Illuminate\Support\Carbon::parse($date)->format('Y-m-d') }}" @selected($selectedDate===\Illuminate\Support\Carbon::parse($date)->format('Y-m-d'))>{{ \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') }}</option>@endforeach</select></div><div class="form-actions"><button class="btn" type="submit">Tampilkan Production Plan</button><a class="btn secondary" href="{{ route('transactions.dandori') }}">Reset</a></div></form></div>
@if(!$readOnly)
<div class="two-col">
    <div class="card">
        <h2>Buat Persiapan Material</h2>
        <form method="POST" action="{{ route('transactions.dandori.store') }}">@csrf
            <div class="field"><label>Production Plan / Finished Good *</label><select name="plan_id" id="plan-id" required><option value="">Pilih production plan</option>@foreach($plans as $plan)<option value="{{ $plan->plan_id }}" @selected(old('plan_id')==$plan->plan_id)>#{{ $plan->plan_id }} · {{ $plan->finishedGood?->part_number }} — {{ $plan->finishedGood?->part_name }} · {{ $plan->plan_date }} · target {{ number_format((float)$plan->qty_plan,3) }}</option>@endforeach</select></div>
            <div class="field"><label>Jumlah FG yang disiapkan *</label><input name="qty_fg" id="qty-fg" type="number" min="0.001" step="0.001" value="{{ old('qty_fg',1) }}" required></div>
            <div class="card" style="background:#f7f9fc;margin:12px 0;padding:14px"><h3 style="margin-top:0">Kebutuhan Material berdasarkan BOM</h3><div id="bom-summary" class="muted">Pilih Production Plan untuk melihat komponen dan jumlah kebutuhan.</div></div>
            <div class="field"><label>Barcode box komponen *</label><textarea class="scan-input" name="barcodes" rows="5" required placeholder="Pindai satu barcode supplier per baris&#10;Setiap baris mewakili satu box komponen">{{ old('barcodes') }}</textarea><small>Barcode pada box berasal dari supplier. Sistem memvalidasi part dan kuantitas setiap box terhadap BOM Finished Good yang dipilih.</small></div>
            <div class="form-actions"><button class="btn" type="submit">Proses Persiapan &amp; Cetak Part Tag</button></div>
        </form>
    </div>
    <div class="help-panel"><h3>Alur Dandori</h3><p>1. Periksa Production Plan dan pilih Finished Good yang akan diproses.</p><p>2. Sistem menampilkan kebutuhan komponen berdasarkan BOM serta saldo komponen di Incoming Rack.</p><p>3. Ambil box material sesuai kebutuhan dan pindai barcode supplier pada setiap box.</p><p>4. Sistem memvalidasi barcode, jumlah, part, dan lokasi box.</p><p>5. Setelah kebutuhan terpenuhi, sistem mencatat persiapan dan menghasilkan Part Tag baru untuk digunakan pada proses Assembly.</p><p class="muted">Part Tag berbeda dari barcode supplier. Barcode supplier tetap menjadi identitas box komponen, sedangkan Part Tag mengidentifikasi hasil persiapan FG.</p></div>
</div>
@else
<div class="alert info">Mode lihat saja. Persiapan komponen, scanning barcode box, dan pembuatan Part Tag dilakukan oleh Dandori.</div>
@endif
<div class="card"><h2>Riwayat Persiapan Material</h2><div class="table-wrap"><table><thead><tr><th>Part Tag</th><th>Production Plan</th><th>Finished Good</th><th>Qty FG</th><th>Status</th><th>Petugas</th><th>Waktu</th><th>Aksi</th></tr></thead><tbody>@forelse($preparations as $preparation)<tr><td><strong>{{ $preparation->tag_code }}</strong></td><td>#{{ $preparation->plan_id }}</td><td>{{ $preparation->plan?->finishedGood?->part_number }}</td><td>{{ number_format((float)$preparation->qty_fg,3) }}</td><td><span class="badge {{ $preparation->preparation_status==='assembled'?'success':'info' }}">{{ ucfirst($preparation->preparation_status) }}</span></td><td>{{ $preparation->user?->full_name }}</td><td>{{ $preparation->preparation_time }}</td><td><a class="btn secondary small" href="{{ route('transactions.dandori.tag',$preparation->preparation_id) }}" target="_blank">Lihat / Cetak Part Tag</a></td></tr>@empty<tr><td colspan="8" class="empty">Belum ada persiapan material.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><h2>Sisa Box yang Tersedia di Dandori</h2><p class="muted">Box parsial yang masih memiliki saldo dapat digunakan kembali pada persiapan berikutnya.</p><div class="table-wrap"><table><thead><tr><th>Barcode Box</th><th>Part</th><th>Sisa Qty</th><th>Lokasi</th><th>Aksi</th></tr></thead><tbody>@forelse($remainders as $box)<tr><td><strong>{{ $box->box_barcode }}</strong></td><td>{{ $box->part?->part_number }} — {{ $box->part?->part_name }}</td><td>{{ number_format((float)$box->qty_in_box,3) }}</td><td>{{ $box->receiving_area }}</td><td><a class="btn secondary small" href="{{ route('transactions.dandori.box-tag',$box->box_id) }}" target="_blank">Cetak Label Box</a></td></tr>@empty<tr><td colspan="5" class="empty">Tidak ada sisa box di Dandori.</td></tr>@endforelse</tbody></table></div></div>
@endsection
@if(!in_array(auth()->user()->role?->role_name, ['Staf Administrasi','Atasan'], true))
@push('scripts')
<script>
(() => {
    const data = @json($planSummary);
    const planSelect = document.getElementById('plan-id');
    const qtyInput = document.getElementById('qty-fg');
    const target = document.getElementById('bom-summary');
    const fmt = (v) => Number(v || 0).toLocaleString('id-ID', {maximumFractionDigits:3});
    function render() {
        const plan = data[planSelect?.value];
        if (!plan) { target.textContent = 'Pilih Production Plan untuk melihat komponen dan jumlah kebutuhan.'; return; }
        const qty = Math.max(0, Number(qtyInput.value || 0));
        const rows = plan.components.map((component) => {
            const needed = component.qty_per_unit * qty;
            return `<tr><td>${component.part_number} · ${component.part_name}</td><td>${fmt(component.qty_per_unit)}</td><td><strong>${fmt(needed)}</strong></td><td>${fmt(component.rack_stock)}</td><td>${fmt(component.prepared_qty)}</td></tr>`;
        }).join('');
        target.innerHTML = `<p>Target plan: <strong>${fmt(plan.target)} FG</strong> · Sudah disiapkan/assembly: <strong>${fmt(plan.fg_prepared)} FG</strong></p><div class="table-wrap"><table><thead><tr><th>Komponen</th><th>BOM / FG</th><th>Kebutuhan untuk jumlah ini</th><th>Stok Incoming Rack</th><th>Telah disiapkan</th></tr></thead><tbody>${rows || '<tr><td colspan="5">BOM belum tersedia.</td></tr>'}</tbody></table></div>`;
    }
    planSelect?.addEventListener('change', render);
    qtyInput?.addEventListener('input', render);
    render();
})();
</script>
@endpush
@endif

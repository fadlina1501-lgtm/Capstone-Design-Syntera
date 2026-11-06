@extends('layouts.app')
@section('content')
<div class="page-head"><div><h1>Mini Cycle Count Baru</h1><p>Hitung fisik material, masukkan Stock Actual, lalu periksa hasil perbandingannya sebelum diajukan kepada Atasan.</p></div><a class="btn secondary" href="{{ route('mini-cycle-counts.index') }}">← Kembali</a></div>
<form method="POST" action="{{ route('mini-cycle-counts.store') }}">@csrf
    <div class="card"><div class="field" style="max-width:340px"><label>Tanggal penghitungan *</label><input type="date" name="count_date" value="{{ old('count_date',now()->toDateString()) }}" required></div></div>
    <div class="card"><h2>Hasil Penghitungan Fisik</h2><div class="table-wrap"><table><thead><tr><th>Part Number</th><th>Nama Part</th><th>Stock System</th><th>Stock Actual (isi)</th><th>Gap</th><th>Status</th></tr></thead><tbody>
    @foreach($parts as $part)
        @php($system=(float)($systemStocks[$part->part_id] ?? 0))
        <tr><td><strong>{{ $part->part_number }}</strong></td><td>{{ $part->part_name }}</td><td data-system-stock="{{ $system }}">{{ number_format($system,3) }} {{ $part->unitOfMaterial?->uom_name }}</td><td><input data-mcc-actual type="number" name="counts[{{ $part->part_id }}][stock_actual]" min="0" step="0.001" value="{{ old('counts.'.$part->part_id.'.stock_actual') }}" placeholder="jumlah aktual"></td><td data-mcc-gap>—</td><td data-mcc-status><span class="badge info">Belum diisi</span></td></tr>
    @endforeach
    </tbody></table></div><p class="muted" style="font-size:11px;margin-top:12px">Masukkan nilai untuk part yang benar-benar dihitung. Sistem menampilkan perbandingan Stock System dan Stock Actual untuk diperiksa sebelum hasil diajukan. Kosongkan baris part yang tidak dihitung.</p><div class="form-actions"><button class="btn" type="submit">Ajukan Hasil Hitung untuk Diperiksa</button><a class="btn secondary" href="{{ route('mini-cycle-counts.index') }}">Batal</a></div></div>
</form>
@endsection
@push('scripts')
<script>
(() => {
    const fmt = (value) => Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits:3});
    document.querySelectorAll('[data-mcc-actual]').forEach((input) => {
        const row = input.closest('tr');
        const system = Number(row.querySelector('[data-system-stock]')?.dataset.systemStock || 0);
        const gapCell = row.querySelector('[data-mcc-gap]');
        const statusCell = row.querySelector('[data-mcc-status]');
        const update = () => {
            if (input.value === '') { gapCell.textContent = '—'; statusCell.innerHTML = '<span class="badge info">Belum diisi</span>'; return; }
            const gap = Number(input.value) - system;
            gapCell.textContent = fmt(gap);
            statusCell.innerHTML = Math.abs(gap) < 0.0001 ? '<span class="badge success">Match</span>' : '<span class="badge danger">Mismatch</span>';
        };
        input.addEventListener('input', update);
        update();
    });
})();
</script>
@endpush

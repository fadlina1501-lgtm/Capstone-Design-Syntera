@extends('layouts.app')
@section('content')
<div class="page-head">
    <div>
        <h1>{{ $recountParent ? 'STO Ulang' : 'Stock Opname Baru' }} · {{ $areaConfig['area'] }}</h1>
        <p>Masukkan jumlah fisik aktual untuk part yang diperiksa pada area {{ $areaConfig['area'] }}.</p>
    </div>
    <a class="btn secondary" href="{{ route('stock-opnames.index') }}">← Kembali</a>
</div>
<div class="alert info">
    <strong>Area pemeriksaan:</strong> {{ $areaConfig['area'] }}.<br>
    <strong>Lokasi yang dihitung:</strong> {{ implode(', ', $areaConfig['locations']) }}.<br>
    Saldo sistem hanya menjumlahkan stok pada lokasi area tersebut, bukan seluruh lokasi perusahaan.
</div>
@if($recountParent)
    <div class="alert warning">
        <strong>Permintaan STO ulang untuk STO #{{ $recountParent->sto_id }}.</strong>
        {{ $recountParent->review_note }}
    </div>
@endif
<form method="POST" action="{{ route('stock-opnames.store') }}">
    @csrf
    @if($recountParent)<input type="hidden" name="recount_of" value="{{ $recountParent->sto_id }}">@endif
    <div class="card">
        <div class="form-grid">
            <div class="field"><label>Periode STO *</label><input name="sto_period" value="{{ old('sto_period', $recountParent?->sto_period ?? now()->format('Y-m')) }}" placeholder="YYYY-MM" maxlength="30" required></div>
            <div class="field"><label>Tanggal STO *</label><input type="date" name="sto_date" value="{{ old('sto_date', now()->toDateString()) }}" required></div>
        </div>
    </div>
    <div class="card">
        <h2>Penghitungan Fisik · {{ $areaConfig['area'] }}</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Part Number</th><th>Nama Part</th><th>Stock System (area)</th><th>Stock Actual (isi)</th></tr></thead>
                <tbody>
                    @foreach($parts as $part)
                        <tr>
                            <td><strong>{{ $part->part_number }}</strong></td>
                            <td>{{ $part->part_name }}</td>
                            <td>{{ number_format((float) ($systemStocks[$part->part_id] ?? 0), 3) }} {{ $part->unitOfMaterial?->uom_name }}</td>
                            <td><input type="number" name="counts[{{ $part->part_id }}][stock_actual]" min="0" step="0.001" value="{{ old('counts.'.$part->part_id.'.stock_actual') }}" placeholder="Jumlah fisik" aria-label="Stock actual {{ $part->part_number }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="muted" style="font-size:11px;margin-top:12px">Isi minimal satu nilai Stock Actual. Gunakan nilai 0 apabila part memang tidak ditemukan pada lokasi pemeriksaan.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Kirim Hasil STO</button>
            <a class="btn secondary" href="{{ route('stock-opnames.index') }}">Batal</a>
        </div>
    </div>
</form>
@endsection

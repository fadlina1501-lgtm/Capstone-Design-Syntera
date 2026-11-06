@extends('layouts.app')
@section('content')
<div class="page-head">
    <div>
        <h1>Revisi Stock Opname #{{ $opname->sto_id }}</h1>
        <p>Perbaiki data yang salah input setelah submit. Revisi akan disimpan ke Audit Log.</p>
    </div>
    <a class="btn secondary" href="{{ route('stock-opnames.show', $opname->sto_id) }}">← Kembali ke Detail</a>
</div>
<div class="alert info"><strong>Perhatian:</strong> Stock System tidak dihitung ulang karena merupakan snapshot pada saat STO pertama dibuat. Ubah hanya nilai Stock Actual yang memang salah. Gap dan status match/mismatch akan dihitung ulang otomatis.</div>
<form method="POST" action="{{ route('stock-opnames.update', $opname->sto_id) }}" onsubmit="return confirm('Simpan revisi Stock Opname ini? Semua perubahan akan dicatat di Audit Log.')">
    @csrf
    @method('PUT')
    <div class="card">
        <h2>Informasi Stock Opname</h2>
        <div class="form-grid">
            <div class="field"><label>Periode STO *</label><input name="sto_period" value="{{ old('sto_period', $opname->sto_period) }}" maxlength="30" required></div>
            <div class="field"><label>Tanggal STO *</label><input type="date" name="sto_date" value="{{ old('sto_date', \Illuminate\Support\Carbon::parse($opname->sto_date)->toDateString()) }}" required></div>
        </div>
        <p class="muted" style="margin-top:10px">Submit awal: {{ $opname->import_time ?? '—' }} · Dibuat oleh: {{ $opname->creator?->full_name ?? '—' }} · Status: {{ ucfirst($opname->sto_status) }}</p>
    </div>
    <div class="card">
        <h2>Koreksi Hasil Penghitungan</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Part Number</th><th>Nama Part</th><th>Stock System (snapshot)</th><th>Stock Actual tersimpan</th><th>Stock Actual revisi *</th><th>Gap baru</th></tr></thead>
            <tbody>
            @foreach($opname->details as $detail)
                <tr>
                    <td><strong>{{ $detail->part?->part_number }}</strong></td>
                    <td>{{ $detail->part?->part_name }}</td>
                    <td>{{ number_format((float)$detail->stock_system, 3) }} {{ $detail->part?->unitOfMaterial?->uom_name }}</td>
                    <td>{{ number_format((float)$detail->stock_actual, 3) }}</td>
                    <td><input type="number" name="details[{{ $detail->sto_detail_id }}][stock_actual]" min="0" step="0.001" value="{{ old('details.'.$detail->sto_detail_id.'.stock_actual', number_format((float)$detail->stock_actual, 3, '.', '')) }}" required></td>
                    <td>{{ number_format((float)$detail->gap_quantity, 3) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if($opname->details->isEmpty())<p class="empty">STO ini belum memiliki detail yang dapat direvisi.</p>@endif
    </div>
    <div class="card">
        <h2>Alasan Revisi</h2>
        <div class="field"><label>Catatan alasan perubahan *</label><textarea name="revision_reason" rows="3" maxlength="500" minlength="5" required placeholder="Jelaskan data mana yang salah dan dasar koreksinya.">{{ old('revision_reason') }}</textarea></div>
        <div class="form-actions">
            <button class="btn" type="submit" @if($opname->details->isEmpty()) disabled @endif>Simpan Revisi</button>
            <a class="btn secondary" href="{{ route('stock-opnames.show', $opname->sto_id) }}">Batal</a>
        </div>
    </div>
</form>
@endsection

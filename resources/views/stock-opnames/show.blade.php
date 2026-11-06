@extends('layouts.app')
@section('content')
@php($roleName = auth()->user()->role?->role_name)
@php($mismatchCount = $opname->details->where('match_status', 'mismatch')->count())
<div class="page-head">
    <div>
        <h1>Detail Stock Opname #{{ $opname->sto_id }}</h1>
        <p>Periode {{ $opname->sto_period }} · Tanggal {{ $opname->sto_date }} · Dibuat oleh {{ $opname->creator?->full_name ?? '—' }}</p>
    </div>
    <div class="btn-row">
        @if($roleName === 'Staf Administrasi')
            <a class="btn" href="{{ route('stock-opnames.edit', $opname->sto_id) }}">Revisi STO</a>
        @endif
        <a class="btn secondary" href="{{ route('stock-opnames.index') }}">← Kembali</a>
    </div>
</div>
<div class="stats">
    <div class="stat"><div class="stat-top">Status STO</div><div class="stat-num" style="font-size:22px">{{ ucfirst($opname->sto_status) }}</div></div>
    <div class="stat"><div class="stat-top">Part diperiksa</div><div class="stat-num">{{ $opname->details->count() }}</div></div>
    <div class="stat"><div class="stat-top">Part mismatch</div><div class="stat-num" style="color:{{ $mismatchCount ? '#b42318' : '#067647' }}">{{ $mismatchCount }}</div></div>
    <div class="stat"><div class="stat-top">Waktu submit awal</div><div class="stat-num" style="font-size:16px">{{ $opname->import_time ?? '—' }}</div></div>
</div>
<div class="card">
    <h2>Hasil Penghitungan STO</h2>
    <p class="muted">Stock System merupakan snapshot saldo pada saat STO pertama kali disimpan. Revisi hanya memperbaiki Stock Actual dan menghitung ulang Gap serta status kesesuaiannya.</p>
    <div class="table-wrap"><table>
        <thead><tr><th>Part Number</th><th>Nama Part</th><th>Stock System</th><th>Stock Actual</th><th>Gap</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($opname->details as $detail)
            <tr>
                <td><strong>{{ $detail->part?->part_number }}</strong></td>
                <td>{{ $detail->part?->part_name }}</td>
                <td>{{ number_format((float)$detail->stock_system, 3) }} {{ $detail->part?->unitOfMaterial?->uom_name }}</td>
                <td>{{ number_format((float)$detail->stock_actual, 3) }}</td>
                <td style="font-weight:800;color:{{ $detail->match_status === 'mismatch' ? '#b42318' : '#067647' }}">{{ number_format((float)$detail->gap_quantity, 3) }}</td>
                <td><span class="badge {{ $detail->match_status === 'match' ? 'success' : 'danger' }}">{{ ucfirst($detail->match_status) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Belum ada detail penghitungan pada STO ini.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@if($roleName === 'Atasan')
    <div class="card">
        <h2>Ajukan Koreksi kepada Staf Administrasi</h2>
        <p class="muted">Atasan dapat meminta pemeriksaan ulang jika menemukan dugaan kesalahan input. Sesuai pembagian hak akses, Atasan tidak mengubah angka STO secara langsung. Permintaan ini akan dikirim sebagai notifikasi kepada Staf Administrasi.</p>
        <form method="POST" action="{{ route('stock-opnames.request-revision', $opname->sto_id) }}" onsubmit="return confirm('Kirim permintaan koreksi STO ini kepada Staf Administrasi?')">
            @csrf
            <div class="field"><label>Alasan permintaan koreksi *</label><textarea name="request_reason" rows="3" maxlength="500" required placeholder="Contoh: Mohon periksa kembali Stock Actual untuk part ... karena hasil hitung fisik pada lembar pemeriksaan berbeda.">{{ old('request_reason') }}</textarea></div>
            <div class="form-actions"><button class="btn" type="submit">Kirim Permintaan Koreksi</button></div>
        </form>
    </div>
@endif
@endsection

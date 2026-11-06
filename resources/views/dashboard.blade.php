@extends('layouts.app')
@section('content')
@php($dashboardRole = auth()->user()->role?->role_name)
@php($canSeeDiscrepancy = in_array($dashboardRole, ['Staf Administrasi','Atasan','Operator Warehouse','Operator Assembly','Operator Delivery'], true))
<div class="page-head"><div><h1>Dashboard Overview</h1><p>Ringkasan kondisi persediaan, aktivitas material, dan tindak lanjut terbaru.</p></div><span class="badge info">Prototype · {{ now()->format('d M Y') }}</span></div>
<div class="stats">
    <div class="stat"><div class="stat-top">Master Part <span class="stat-icon">▦</span></div><div class="stat-num">{{ number_format($partCount) }}</div><div class="stat-foot">Part terdaftar dalam sistem</div></div>
    <div class="stat"><div class="stat-top">Total Stock Tercatat <span class="stat-icon">▣</span></div><div class="stat-num">{{ number_format($stockQty,3) }}</div><div class="stat-foot">Akumulasi saldo pada semua lokasi</div></div>
    <div class="stat"><div class="stat-top">Movement Hari Ini <span class="stat-icon">↔</span></div><div class="stat-num">{{ number_format($todayMovements) }}</div><div class="stat-foot">Pencatatan perpindahan material</div></div>
    @if($canSeeDiscrepancy)<div class="stat"><div class="stat-top">Inventory Accuracy <span class="stat-icon">✓</span></div><div class="stat-num">{{ $accuracy === null ? '—' : number_format($accuracy,2).'%' }}</div><div class="stat-foot">{{ $latestSto ? 'Berdasarkan STO '.$latestSto->sto_period : 'Belum ada hasil Stock Opname' }}</div></div>@endif
</div>
<div class="stats">
    @if($canSeeDiscrepancy)<div class="stat"><div class="stat-top">Discrepancy pada STO Terbaru <span class="stat-icon">!</span></div><div class="stat-num">{{ number_format($mismatchCount) }}</div><div class="stat-foot">{{ $latestSto ? 'Baris part mismatch' : 'Belum ada data STO' }}</div></div>@endif
    <div class="stat"><div class="stat-top">Status Sistem <span class="stat-icon">●</span></div><div class="stat-num" style="font-size:21px">Aktif</div><div class="stat-foot">Akses menggunakan role pengguna</div></div>
    <div class="stat"><div class="stat-top">Fokus Sistem <span class="stat-icon">⌖</span></div><div class="stat-num" style="font-size:21px">5 proses</div><div class="stat-foot">Incoming Warehouse sampai Delivery</div></div>
</div>
<div class="card"><div class="page-head" style="margin-bottom:12px"><div><h2>Pergerakan Material Terbaru</h2><p>Catatan barcode pada aktivitas material.</p></div><a class="btn secondary small" href="{{ route('monitoring.movements') }}">Lihat Stock Movement</a></div><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Barcode / Part</th><th>Perpindahan</th><th>Jumlah</th></tr></thead><tbody>@forelse($recentMovements as $m)<tr><td>{{ \Carbon\Carbon::parse($m->scan_time)->format('d/m H:i') }}</td><td><strong>{{ $m->box_barcode }}</strong><br><span class="muted">{{ $m->part_number }} · {{ $m->part_name }}</span></td><td>{{ $m->from_location ?? 'Penerimaan awal' }}<br><span class="muted">↓ {{ $m->to_location }}</span></td><td>{{ number_format((float)$m->quantity,3) }}</td></tr>@empty<tr><td colspan="4" class="empty">Belum ada transaksi. Gunakan menu Incoming Warehouse untuk memulai.</td></tr>@endforelse</tbody></table></div></div>
@endsection

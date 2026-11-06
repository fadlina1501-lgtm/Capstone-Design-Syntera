@extends('layouts.app')
@section('content')
@php($readOnly = in_array(auth()->user()->role?->role_name, ['Staf Administrasi','Atasan'], true))
<div class="page-head"><div><h1>Incoming Rack</h1><p>Catat perpindahan box material dari Incoming Warehouse menuju Incoming Rack.</p></div></div>
@if(!$readOnly)
<div class="two-col"><div class="card"><h2>Scan Perpindahan ke Rack</h2><form method="POST" action="{{ route('transactions.incoming-rack.store') }}">@csrf<div class="field"><label>Barcode box supplier *</label><input class="scan-input" name="box_barcode" required autofocus placeholder="Scan barcode yang sama dari Incoming Warehouse"></div><div class="form-actions"><button class="btn" type="submit">Catat Perpindahan</button></div></form></div><div class="help-panel"><h3>Aturan proses</h3><p>1. Ambil box yang telah dicatat pada Incoming Warehouse.</p><p>2. Pindai barcode supplier pada box sebelum ditempatkan di Incoming Rack.</p><p>3. Sistem memvalidasi lokasi box, mengurangi saldo Incoming Warehouse, menambah saldo Incoming Rack, dan menyimpan riwayat perpindahan.</p></div></div>
@else
<div class="alert info">Mode lihat saja. Pencatatan perpindahan dari Incoming Warehouse ke Incoming Rack dilakukan oleh Operator Warehouse.</div>
@endif
<div class="card"><h2>Riwayat Perpindahan ke Incoming Rack</h2><div class="table-wrap"><table><thead><tr><th>Barcode</th><th>Part</th><th>Dari</th><th>Ke</th><th>Jumlah</th><th>Waktu</th><th>Petugas</th></tr></thead><tbody>@forelse($recent as $movement)<tr><td><strong>{{ $movement->box?->box_barcode }}</strong></td><td>{{ $movement->box?->part?->part_number }} — {{ $movement->box?->part?->part_name }}</td><td>{{ $movement->fromLocation?->location_name ?? '—' }}</td><td>{{ $movement->toLocation?->location_name }}</td><td>{{ number_format((float)$movement->quantity,3) }}</td><td>{{ $movement->scan_time }}</td><td>{{ $movement->user?->full_name }}</td></tr>@empty<tr><td colspan="7" class="empty">Belum ada perpindahan ke Incoming Rack.</td></tr>@endforelse</tbody></table></div></div>
@endsection

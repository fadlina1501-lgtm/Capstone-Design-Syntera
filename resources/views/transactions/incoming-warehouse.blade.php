@extends('layouts.app')
@section('content')
@php($readOnly = in_array(auth()->user()->role?->role_name, ['Staf Administrasi','Atasan'], true))
<div class="page-head"><div><h1>Incoming Warehouse</h1><p>Verifikasi barcode supplier dan catat material yang diterima sebelum disimpan pada Incoming Warehouse.</p></div></div>
@if(!$readOnly)
<div class="two-col">
    <div class="card">
        <h2>Catat Penerimaan Material</h2>
        <form method="POST" action="{{ route('transactions.incoming-warehouse.store') }}">
            @csrf
            <div class="field"><label>Barcode box supplier *</label><input class="scan-input" name="box_barcode" required autofocus placeholder="Scan barcode yang sudah terpasang pada box supplier"></div>
            <div class="form-grid">
                <div class="field"><label>Part *</label><select name="part_id" required><option value="">Pilih part</option>@foreach($parts as $part)<option value="{{ $part->part_id }}" @selected(old('part_id')==$part->part_id)>{{ $part->part_number }} — {{ $part->part_name }}</option>@endforeach</select></div>
                <div class="field"><label>Supplier</label><select name="supplier_id"><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id')==$supplier->supplier_id)>{{ $supplier->supplier_code }} — {{ $supplier->supplier_name }}</option>@endforeach</select></div>
                <div class="field"><label>Jumlah dalam box *</label><input type="number" name="qty_in_box" step="0.001" min="0.001" value="{{ old('qty_in_box',1) }}" required></div>
                <div class="field"><label>Nomor PO</label><input name="po_number" value="{{ old('po_number') }}"></div>
                <div class="field"><label>Tanggal pengiriman / penerimaan</label><input type="date" name="delivery_date" value="{{ old('delivery_date',now()->toDateString()) }}"></div>
                <div class="field"><label>Nomor urut box</label><input type="number" name="box_sequence" min="1" value="{{ old('box_sequence',1) }}"></div>
                <div class="field"><label>Rak / shelf</label><input name="shelf_no" value="{{ old('shelf_no') }}" placeholder="Contoh: D1-1"></div>
            </div>
            <div class="form-actions"><button class="btn" type="submit">Simpan Penerimaan</button></div>
        </form>
    </div>
    <div class="help-panel"><h3>Alur Incoming Warehouse</h3>
        <p>1. Staf Administrasi memeriksa Delivery Notes, jumlah, dan kondisi material.</p>
        <p>2. Operator Warehouse memindai barcode yang telah terpasang pada box dari supplier.</p>
        <p>3. Sistem membaca barcode dan mengisikan informasi yang tersedia. Periksa kembali informasi hasil pemindaian.</p>
        <p>4. Simpan penerimaan untuk mencatat box dan saldo pada lokasi Incoming Warehouse.</p>
        <p>5. Saat box dipindahkan, pindai barcode yang sama pada menu Incoming Rack.</p>
        <p class="muted">Untuk demo, QR supplier dapat berisi identitas box, part number, supplier, quantity, PO, tanggal, urutan box, dan shelf. Data hasil scan tetap perlu diperiksa sebelum disimpan.</p>
    </div>
</div>
@else
<div class="alert info">Mode lihat saja. Staf Administrasi dan Atasan dapat memeriksa riwayat penerimaan, sedangkan pencatatan penerimaan dilakukan oleh Operator Warehouse setelah pemeriksaan material.</div>
@endif
<div class="card"><h2>{{ $readOnly ? 'Data Penerimaan Material' : 'Box Terbaru' }}</h2><div class="table-wrap"><table><thead><tr><th>Barcode</th><th>Part</th><th>Supplier</th><th>PO</th><th>Tanggal</th><th>Qty Box</th><th>Lokasi</th><th>Status</th></tr></thead><tbody>
@forelse($boxes as $box)
<tr><td><strong>{{ $box->box_barcode }}</strong></td><td>{{ $box->part?->part_number }}<br><span class="muted">{{ $box->part?->part_name }}</span></td><td>{{ $box->supplier?->supplier_name ?? '—' }}</td><td>{{ $box->po_number ?? '—' }}</td><td>{{ $box->delivery_date ?? '—' }}</td><td>{{ number_format((float)$box->qty_in_box,3) }}</td><td>{{ $box->receiving_area ?? '—' }}</td><td><span class="badge info">{{ $box->box_status }}</span></td></tr>
@empty<tr><td colspan="8" class="empty">Belum ada box yang diterima. Mulai dengan memindai barcode supplier pada Incoming Warehouse.</td></tr>@endforelse
</tbody></table></div></div>
@endsection

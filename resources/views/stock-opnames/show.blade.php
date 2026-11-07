@extends('layouts.app')
@section('content')
@php
    $roleName = auth()->user()->role?->role_name;
    $roleArea = [
        'Operator Warehouse' => 'Warehouse',
        'Dandori' => 'Dandori',
        'Operator Assembly' => 'Assembly',
        'Operator Delivery' => 'Delivery',
    ][$roleName] ?? null;
    $mismatchCount = $opname->details->where('match_status', 'mismatch')->count();
    $statusLabels = [
        'submitted' => ['Menunggu Persetujuan', 'warning'],
        'approved' => ['Disetujui', 'success'],
        'recount_requested' => ['Perlu STO Ulang', 'danger'],
        'recount_submitted' => ['STO Ulang Dikirim', 'info'],
        'completed' => ['Selesai', 'success'],
    ];
    [$statusLabel, $statusColor] = $statusLabels[$opname->sto_status] ?? [ucfirst(str_replace('_', ' ', $opname->sto_status)), 'info'];
    $canReview = $roleName === 'Atasan' && $opname->sto_status === 'submitted';
    $canRecount = $roleArea === $opname->sto_area && $opname->sto_status === 'recount_requested';
@endphp
<div class="page-head">
    <div>
        <h1>Detail Stock Opname #{{ $opname->sto_id }} · {{ $opname->sto_area }}</h1>
        <p>Periode {{ $opname->sto_period }} · Tanggal {{ $opname->sto_date?->format('Y-m-d') ?? $opname->sto_date }} · Dibuat oleh {{ $opname->creator?->full_name ?? '—' }}</p>
    </div>
    <a class="btn secondary" href="{{ route('stock-opnames.index') }}">← Kembali</a>
</div>
<div class="stats">
    <div class="stat"><div class="stat-top">Area STO</div><div class="stat-num" style="font-size:22px">{{ $opname->sto_area }}</div></div>
    <div class="stat"><div class="stat-top">Status</div><div class="stat-num" style="font-size:18px"><span class="badge {{ $statusColor }}">{{ $statusLabel }}</span></div></div>
    <div class="stat"><div class="stat-top">Part diperiksa</div><div class="stat-num">{{ $opname->details->count() }}</div></div>
    <div class="stat"><div class="stat-top">Part mismatch</div><div class="stat-num" style="color:{{ $mismatchCount ? '#b42318' : '#067647' }}">{{ $mismatchCount }}</div></div>
</div>
<div class="card">
    <h2>Informasi Pemeriksaan</h2>
    <p class="muted">Stock System merupakan snapshot saldo pada lokasi area {{ $opname->sto_area }} saat STO dikirim. Selisih dihitung dari Stock Actual dikurangi Stock System.</p>
    <div class="form-grid">
        <div><strong>Waktu submit:</strong><br>{{ $opname->import_time?->format('d-m-Y H:i') ?? $opname->import_time ?? '—' }}</div>
        <div><strong>Diperiksa oleh:</strong><br>{{ $opname->reviewer?->full_name ?? 'Belum diperiksa' }}</div>
        <div><strong>Waktu pemeriksaan:</strong><br>{{ $opname->reviewed_at?->format('d-m-Y H:i') ?? '—' }}</div>
        <div><strong>STO sebelumnya:</strong><br>
            @if($opname->recountParent)
                <a href="{{ route('stock-opnames.show', $opname->recountParent->sto_id) }}">STO #{{ $opname->recountParent->sto_id }}</a>
            @else
                —
            @endif
        </div>
    </div>
    @if($opname->review_note)
        <div class="alert {{ $opname->sto_status === 'recount_requested' ? 'warning' : 'info' }}" style="margin-top:16px;margin-bottom:0"><strong>Catatan Atasan:</strong> {{ $opname->review_note }}</div>
    @endif
</div>
<div class="card">
    <h2>Hasil Penghitungan STO</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Part Number</th><th>Nama Part</th><th>Stock System</th><th>Stock Actual</th><th>Gap</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($opname->details as $detail)
                    <tr>
                        <td><strong>{{ $detail->part?->part_number }}</strong></td>
                        <td>{{ $detail->part?->part_name }}</td>
                        <td>{{ number_format((float) $detail->stock_system, 3) }} {{ $detail->part?->unitOfMaterial?->uom_name }}</td>
                        <td>{{ number_format((float) $detail->stock_actual, 3) }}</td>
                        <td style="font-weight:800;color:{{ $detail->match_status === 'mismatch' ? '#b42318' : '#067647' }}">{{ number_format((float) $detail->gap_quantity, 3) }}</td>
                        <td><span class="badge {{ $detail->match_status === 'match' ? 'success' : 'danger' }}">{{ ucfirst($detail->match_status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Belum ada detail penghitungan pada STO ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($canRecount)
    <div class="card">
        <h2>STO Ulang Diminta Atasan</h2>
        <p class="muted">Periksa catatan Atasan, lakukan penghitungan fisik ulang pada area {{ $opname->sto_area }}, lalu kirim hasil baru. Hasil sebelumnya tetap disimpan sebagai riwayat.</p>
        <a class="btn" href="{{ route('stock-opnames.create', ['recount_of' => $opname->sto_id]) }}">Buat STO Ulang</a>
    </div>
@endif
@if($canReview)
    <div class="card">
        <h2>Keputusan Atasan</h2>
        <p class="muted">Periksa hasil penghitungan. Setujui apabila hasil sudah dapat diterima, atau minta petugas area melakukan STO ulang apabila perlu pemeriksaan fisik kembali.</p>
        <div class="two-col" style="grid-template-columns:repeat(2,minmax(0,1fr));align-items:start">
            <form method="POST" action="{{ route('stock-opnames.approve', $opname->sto_id) }}" onsubmit="return confirm('Setujui hasil Stock Opname area {{ $opname->sto_area }}?')">
                @csrf
                <div class="field"><label>Catatan persetujuan (opsional)</label><textarea name="review_note" rows="3" maxlength="500" placeholder="Catatan untuk petugas area"></textarea></div>
                <button class="btn" type="submit">Approve STO</button>
            </form>
            <form method="POST" action="{{ route('stock-opnames.request-recount', $opname->sto_id) }}" onsubmit="return confirm('Minta petugas {{ $opname->sto_area }} melakukan STO ulang?')">
                @csrf
                <div class="field"><label>Alasan meminta STO ulang *</label><textarea name="request_reason" rows="3" minlength="5" maxlength="500" required placeholder="Jelaskan bagian yang perlu dihitung ulang dan alasannya."></textarea></div>
                <button class="btn danger" type="submit">Minta STO Ulang</button>
            </form>
        </div>
    </div>
@endif
@if($opname->recounts->isNotEmpty())
    <div class="card">
        <h2>Riwayat STO Ulang</h2>
        <div class="table-wrap"><table><thead><tr><th>ID STO</th><th>Tanggal</th><th>Dibuat oleh</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @foreach($opname->recounts as $recount)
                @php([$recountLabel, $recountColor] = $statusLabels[$recount->sto_status] ?? [ucfirst(str_replace('_', ' ', $recount->sto_status)), 'info'])
                <tr><td>#{{ $recount->sto_id }}</td><td>{{ $recount->sto_date?->format('Y-m-d') ?? $recount->sto_date }}</td><td>{{ $recount->creator?->full_name ?? '—' }}</td><td><span class="badge {{ $recountColor }}">{{ $recountLabel }}</span></td><td><a class="btn secondary small" href="{{ route('stock-opnames.show', $recount->sto_id) }}">Detail</a></td></tr>
            @endforeach
        </tbody></table></div>
    </div>
@endif
@endsection

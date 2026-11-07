@extends('layouts.app')
@section('content')
@php
    $roleName = auth()->user()->role?->role_name;
    $isOperational = in_array($roleName, ['Operator Warehouse', 'Dandori', 'Operator Assembly', 'Operator Delivery'], true);
    $statusLabels = [
        'submitted' => ['Menunggu Persetujuan', 'warning'],
        'approved' => ['Disetujui', 'success'],
        'recount_requested' => ['Perlu STO Ulang', 'danger'],
        'recount_submitted' => ['STO Ulang Dikirim', 'info'],
        'completed' => ['Selesai', 'success'],
    ];
@endphp
<div class="page-head">
    <div>
        <h1>Stock Opname</h1>
        @if($isOperational)
            <p>Catat dan pantau hasil penghitungan fisik untuk area {{ $areaConfig['area'] }}.</p>
        @elseif($roleName === 'Atasan')
            <p>Periksa hasil Stock Opname seluruh area, lalu setujui atau minta penghitungan ulang.</p>
        @else
            <p>Monitoring hasil Stock Opname dari seluruh area. Staf Administrasi memiliki akses lihat saja.</p>
        @endif
    </div>
    @if($isOperational)
        <a class="btn" href="{{ route('stock-opnames.create') }}">+ Buat STO {{ $areaConfig['area'] }}</a>
    @endif
</div>
@if($roleName === 'Staf Administrasi')
    <div class="alert info">Mode monitoring: Staf Administrasi dapat melihat seluruh hasil STO, tetapi tidak dapat membuat, mengubah, atau menyetujui STO.</div>
@elseif($roleName === 'Atasan')
    <div class="alert info">Menunggu persetujuan: periksa detail setiap STO. Atasan dapat menyetujui hasil atau meminta petugas area melakukan STO ulang.</div>
@endif
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Area</th><th>Periode</th><th>Tanggal</th><th>Status</th><th>Dibuat oleh</th><th>Waktu input</th><th>Part mismatch</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opnames as $s)
                    @php([$statusLabel, $statusColor] = $statusLabels[$s->sto_status] ?? [ucfirst(str_replace('_', ' ', $s->sto_status)), 'info'])
                    <tr>
                        <td><strong>{{ $s->sto_area }}</strong></td>
                        <td>{{ $s->sto_period }}</td>
                        <td>{{ $s->sto_date?->format('Y-m-d') ?? $s->sto_date }}</td>
                        <td><span class="badge {{ $statusColor }}">{{ $statusLabel }}</span></td>
                        <td>{{ $s->creator?->full_name ?? '—' }}</td>
                        <td>{{ $s->import_time?->format('d-m-Y H:i') ?? $s->import_time ?? '—' }}</td>
                        <td><span class="badge {{ $s->mismatch_count ? 'danger' : 'success' }}">{{ $s->mismatch_count }}</span></td>
                        <td><a class="btn secondary small" href="{{ route('stock-opnames.show', $s->sto_id) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Belum ada data Stock Opname untuk area ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $opnames->links() }}</div>
</div>
@endsection

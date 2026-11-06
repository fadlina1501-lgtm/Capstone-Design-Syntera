@extends('layouts.app')
@section('content')
<div class="page-head">
    <div>
        <h1>Stock Opname</h1>
        <p>Bandingkan saldo sistem dengan hasil penghitungan fisik sebagai dasar monitoring inventory accuracy.</p>
    </div>
    @if(auth()->user()->role?->role_name === 'Staf Administrasi')
        <a class="btn" href="{{ route('stock-opnames.create') }}">+ Stock Opname Baru</a>
    @endif
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Periode</th><th>Tanggal</th><th>Status</th><th>Dibuat oleh</th><th>Waktu input</th><th>Part mismatch</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opnames as $s)
                    <tr>
                        <td><strong>{{ $s->sto_period }}</strong></td>
                        <td>{{ $s->sto_date }}</td>
                        <td><span class="badge success">{{ ucfirst($s->sto_status) }}</span></td>
                        <td>{{ $s->creator?->full_name }}</td>
                        <td>{{ $s->import_time }}</td>
                        <td><span class="badge {{ $s->mismatch_count ? 'danger' : 'success' }}">{{ $s->mismatch_count }}</span></td>
                        <td>
                            <div class="btn-row" style="flex-wrap:nowrap">
                                <a class="btn secondary small" href="{{ route('stock-opnames.show', $s->sto_id) }}">Detail</a>
                                @if(auth()->user()->role?->role_name === 'Staf Administrasi')
                                    <a class="btn small" href="{{ route('stock-opnames.edit', $s->sto_id) }}">Revisi</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Belum ada data Stock Opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $opnames->links() }}</div>
</div>
@endsection

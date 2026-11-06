<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Part Tag {{ $preparation->tag_code }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="background:#f4f7fb">
    <div class="tag-card part-tag-card">
        <div style="font-size:12px;letter-spacing:1px;font-weight:800;color:#64748b">PT. XYZ · LINE AIJ</div>
        <h2 style="margin:8px 0 4px">PART TAG / PREPARATION TAG</h2>
        <div class="part-tag-qr-wrap">
            <div class="part-tag-qr" data-stock-qr data-value="{{ $preparation->tag_code }}" aria-label="QR code Part Tag"></div>
            <div class="part-tag-code">{{ $preparation->tag_code }}</div>
            <p class="muted" style="font-size:11px;margin:4px 0 0">Pindai QR ini pada proses Assembly dan Delivery.</p>
        </div>
        <div class="part-tag-meta">
            <div><strong>Finished Good:</strong> {{ $preparation->plan?->finishedGood?->part_number }} — {{ $preparation->plan?->finishedGood?->part_name }}</div>
            <div><strong>Production Plan:</strong> #{{ $preparation->plan_id }} · {{ $preparation->plan?->plan_date }}</div>
            <div><strong>Qty FG:</strong> {{ number_format((float)$preparation->qty_fg,3) }}</div>
            <div><strong>Waktu persiapan:</strong> {{ $preparation->preparation_time }}</div>
        </div>
        <h3>Komponen yang disiapkan</h3>
        <table>
            <thead><tr><th>Barcode box</th><th>Part Number</th><th>Qty prepared</th></tr></thead>
            <tbody>
                @foreach($preparation->details as $detail)
                    <tr>
                        <td>{{ $detail->box?->box_barcode }}</td>
                        <td>{{ $detail->box?->part?->part_number }}</td>
                        <td>{{ number_format((float)$detail->qty_prepared,3) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="print-hide" style="margin-top:20px">
            <button class="btn" onclick="window.print()">Cetak Part Tag</button>
            <button class="btn secondary" onclick="window.close()">Tutup</button>
        </div>
    </div>
    <script src="{{ asset('js/qr-lite.js') }}"></script>
</body>
</html>

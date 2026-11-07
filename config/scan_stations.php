<?php

return [
    // For permanent printed station QR codes, set this to a stable HTTPS origin
    // (for example a reserved ngrok domain). Leave blank to use the current host.
    'base_url' => env('SCAN_STATION_BASE_URL'),

    'stations' => [
        'incoming-warehouse' => [
            'label' => 'Incoming Warehouse',
            'desk' => 'Meja Operator Warehouse · Penerimaan',
            'role' => 'Operator Warehouse',
            'description' => 'Scan QR supplier pada box, periksa data penerimaan, lalu simpan dari HP.',
            'form' => 'incoming-warehouse',
            'post_route' => 'transactions.incoming-warehouse.store',
        ],
        'incoming-rack' => [
            'label' => 'Incoming Rack',
            'desk' => 'Meja Operator Warehouse · Penempatan Rack',
            'role' => 'Operator Warehouse',
            'description' => 'Scan barcode supplier pada box yang dipindahkan dari Incoming Warehouse ke Incoming Rack.',
            'form' => 'incoming-rack',
            'post_route' => 'transactions.incoming-rack.store',
        ],
        'dandori' => [
            'label' => 'Dandori',
            'desk' => 'Meja Operator Dandori',
            'role' => 'Dandori',
            'description' => 'Pilih Production Plan, tentukan jumlah FG, scan box komponen sesuai BOM, lalu proses persiapan.',
            'form' => 'dandori',
            'post_route' => 'transactions.dandori.store',
        ],
        'assembly' => [
            'label' => 'Assembly',
            'desk' => 'Meja Operator Assembly',
            'role' => 'Operator Assembly',
            'description' => 'Scan Part Tag hasil Dandori, masukkan jumlah Finished Good, lalu catat hasil Assembly dari HP.',
            'form' => 'assembly',
            'post_route' => 'transactions.assembly.store',
        ],
        'part-ng' => [
            'label' => 'Part NG',
            'desk' => 'Meja Operator Assembly · Pencatatan NG',
            'role' => 'Operator Assembly',
            'description' => 'Catat part/produk NG, Production Plan bila diketahui, jumlah, dan alasan ketidaksesuaian dari HP.',
            'form' => 'part-ng',
            'post_route' => 'transactions.part-ng.store',
        ],
        'delivery' => [
            'label' => 'Delivery',
            'desk' => 'Meja Operator Delivery',
            'role' => 'Operator Delivery',
            'description' => 'Pilih tahap perpindahan, scan Part Tag FG, lalu catat perpindahan ke Outgoing Area atau Loading Area dari HP.',
            'form' => 'delivery',
            'post_route' => 'transactions.delivery.store',
        ],
    ],
];

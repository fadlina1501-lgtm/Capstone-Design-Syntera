<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Stock Tracking' }} · Line AIJ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $roleName = auth()->user()?->role?->role_name ?? '';
    $admin = $roleName === 'Staf Administrasi';
    $atasan = $roleName === 'Atasan';
    $warehouse = $roleName === 'Operator Warehouse';
    $dandori = $roleName === 'Dandori';
    $assembly = $roleName === 'Operator Assembly';
    $delivery = $roleName === 'Operator Delivery';
    $management = $admin || $atasan;
    $active = fn($patterns) => request()->routeIs(...(array) $patterns) ? 'active' : '';
    $currentUser = auth()->user();
    $unreadNotificationCount = $currentUser ? $currentUser->notifications()->where('is_read', false)->count() : 0;
    $headerNotifications = $currentUser ? $currentUser->notifications()->orderByDesc('notification_id')->limit(5)->get() : collect();
@endphp
<div class="shell">
    <aside class="sidebar">
        <div class="brand"><div class="brand-mark">ST</div><div><strong>Stock Tracking</strong><small>LINE AIJ · PT. XYZ</small></div></div>
        <nav aria-label="Navigasi utama">
            <div class="nav-label">Workspace</div>
            <a class="nav-link {{ $active('dashboard') }}" href="{{ route('dashboard') }}"><span class="nav-ico">▦</span>Dashboard</a>

            @if($management)
                @php $masterExpanded = request()->routeIs('master.*'); @endphp
                <section class="nav-group {{ $masterExpanded ? 'is-open has-current' : '' }}">
                    <button class="nav-group-toggle {{ $masterExpanded ? 'is-current' : '' }}" type="button" data-nav-toggle aria-expanded="{{ $masterExpanded ? 'true' : 'false' }}" aria-controls="nav-group-master">
                        <span>Master Data</span><span class="nav-group-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-group-items" id="nav-group-master" @if(!$masterExpanded) hidden @endif>
                        @foreach([['units','Unit of Material'],['parts','Part / Material'],['packaging-quantities','Box Packaging Quantity'],['bom','Bill of Material'],['suppliers','Supplier'],['production-plans','Production Plan'],['manpowers','ManPower'],['locations','Lokasi Proses']] as [$entity,$label])
                            <a class="nav-link {{ request()->route('entity') === $entity ? 'active' : '' }}" href="{{ route('master.index',$entity) }}"><span class="nav-ico">◫</span>{{ $label }}</a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($dandori || $assembly || $delivery)
                <div class="nav-label">Perencanaan Produksi</div>
                <a class="nav-link {{ $active('production-plans.*') }}" href="{{ route('production-plans.index') }}"><span class="nav-ico">▥</span>Production Plan</a>
            @endif

            @if($management || $warehouse || $dandori || $assembly || $delivery)
                @php $transactionsExpanded = request()->routeIs('transactions.*'); @endphp
                <section class="nav-group {{ $transactionsExpanded ? 'is-open has-current' : '' }}">
                    <button class="nav-group-toggle {{ $transactionsExpanded ? 'is-current' : '' }}" type="button" data-nav-toggle aria-expanded="{{ $transactionsExpanded ? 'true' : 'false' }}" aria-controls="nav-group-transactions">
                        <span>Transaksi Material</span><span class="nav-group-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-group-items" id="nav-group-transactions" @if(!$transactionsExpanded) hidden @endif>
                        @if($management || $warehouse)
                            <a class="nav-link {{ $active('transactions.incoming-warehouse') }}" href="{{ route('transactions.incoming-warehouse') }}"><span class="nav-ico">↓</span>Incoming Warehouse</a>
                            <a class="nav-link {{ $active('transactions.incoming-rack') }}" href="{{ route('transactions.incoming-rack') }}"><span class="nav-ico">⇢</span>Incoming Rack</a>
                        @endif
                        @if($management || $dandori)
                            <a class="nav-link {{ $active(['transactions.dandori','transactions.dandori.tag','transactions.dandori.box-tag']) }}" href="{{ route('transactions.dandori') }}"><span class="nav-ico">▤</span>Dandori</a>
                        @endif
                        @if($management || $assembly)
                            <a class="nav-link {{ $active('transactions.assembly') }}" href="{{ route('transactions.assembly') }}"><span class="nav-ico">⚙</span>Assembly</a>
                            <a class="nav-link {{ $active('transactions.part-ng') }}" href="{{ route('transactions.part-ng') }}"><span class="nav-ico">!</span>Part NG</a>
                        @endif
                        @if($management || $delivery)
                            <a class="nav-link {{ $active('transactions.delivery') }}" href="{{ route('transactions.delivery') }}"><span class="nav-ico">↑</span>Delivery</a>
                        @endif
                    </div>
                </section>
            @endif

            @php $inventoryExpanded = request()->routeIs('monitoring.stocks', 'monitoring.movements', 'monitoring.discrepancies', 'stock-opnames.*'); @endphp
            <section class="nav-group {{ $inventoryExpanded ? 'is-open has-current' : '' }}">
                <button class="nav-group-toggle {{ $inventoryExpanded ? 'is-current' : '' }}" type="button" data-nav-toggle aria-expanded="{{ $inventoryExpanded ? 'true' : 'false' }}" aria-controls="nav-group-inventory">
                    <span>Monitoring Persediaan</span><span class="nav-group-chevron" aria-hidden="true">⌄</span>
                </button>
                <div class="nav-group-items" id="nav-group-inventory" @if(!$inventoryExpanded) hidden @endif>
                    <a class="nav-link {{ $active('monitoring.stocks') }}" href="{{ route('monitoring.stocks') }}"><span class="nav-ico">▣</span>Stock Monitoring</a>
                    <a class="nav-link {{ $active('monitoring.movements') }}" href="{{ route('monitoring.movements') }}"><span class="nav-ico">↔</span>Stock Movement</a>
                    @if($management || $warehouse || $assembly || $delivery)
                        <a class="nav-link {{ $active('monitoring.discrepancies') }}" href="{{ route('monitoring.discrepancies') }}"><span class="nav-ico">!</span>Discrepancy</a>
                    @endif
                    @if($management)
                        <a class="nav-link {{ $active('stock-opnames.*') }}" href="{{ route('stock-opnames.index') }}"><span class="nav-ico">▤</span>Stock Opname</a>
                    @endif
                </div>
            </section>

            @if($management)
                <div class="nav-label">Monitoring Produksi</div>
                <a class="nav-link {{ $active('monitoring.production') }}" href="{{ route('monitoring.production') }}"><span class="nav-ico">▥</span>Daily Production</a>
                <div class="nav-label">Laporan</div>
                <a class="nav-link {{ $active('reports.*') }}" href="{{ route('reports.index') }}"><span class="nav-ico">▥</span>Reports</a>
            @endif
            <div class="nav-label">Sistem</div>
            <a class="nav-link {{ $active('notifications.*') }}" href="{{ route('notifications.index') }}"><span class="nav-ico">♧</span>Notifikasi</a>
        </nav>
        <div class="sidebar-bottom">Prototype Capstone Design Project<br>Inventory Accuracy &amp; Traceability</div>
    </aside>
    <section class="workspace">
        <header class="topbar">
            <div class="topbar-heading"><div class="page-title">{{ $topTitle ?? ($title ?? 'Sistem Stock Tracking') }}</div><div class="page-subtitle">Pengendalian dan traceability aliran material</div></div>
            <div class="topbar-actions">
                <div class="notification-widget">
                    <button type="button" class="notification-bell" data-notification-bell data-unread-url="{{ route('notifications.unread-count') }}" aria-label="Notifikasi{{ $unreadNotificationCount > 0 ? ', '.$unreadNotificationCount.' belum dibaca' : '' }}" aria-expanded="false" aria-controls="notification-popover" title="Notifikasi">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
                        @if($unreadNotificationCount > 0)<span class="notification-badge" data-notification-count>{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>@endif
                    </button>
                    <section class="notification-popover" id="notification-popover" data-notification-popover hidden aria-label="Notifikasi terbaru">
                        <div class="notification-popover-head"><div><strong>Notifikasi</strong><span><b data-notification-unread-text>{{ $unreadNotificationCount }}</b> belum dibaca</span></div><a href="{{ route('notifications.index') }}">Lihat semua</a></div>
                        <div class="notification-preview-list" data-notification-preview-list>
                            @forelse($headerNotifications as $headerNotification)
                                <a href="{{ route('notifications.index') }}" class="notification-preview {{ !$headerNotification->is_read ? 'is-unread' : '' }}"><span class="notification-preview-dot {{ !$headerNotification->is_read ? 'is-unread' : '' }}"></span><span class="notification-preview-copy"><strong>{{ $headerNotification->notification_title }}</strong><span>{{ $headerNotification->notification_message }}</span><small>{{ $headerNotification->created_at?->format('d M Y · H:i') ?? '' }}</small></span></a>
                            @empty
                                <div class="notification-empty"><span class="notification-empty-icon">✓</span><strong>Belum ada notifikasi</strong><span>Informasi terbaru akan muncul di sini.</span></div>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="notification-popover-footer">Buka pusat notifikasi <span>→</span></a>
                    </section>
                </div>
                <div class="user-chip"><div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->full_name ?? 'U',0,1)) }}</div><div class="user-identity"><div class="user-name">{{ auth()->user()->full_name ?? 'Pengguna' }}</div><div class="user-role">{{ $roleName }}</div></div><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn secondary small">Logout</button></form></div>
            </div>
        </header>
        <main>
            @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert error"><strong>Ada yang perlu diperiksa:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </section>
</div>
@stack('scripts')
<script src="{{ asset('js/ui.js') }}" defer></script>
<script src="{{ asset('js/mobile-barcode-scanner.js') }}" defer></script>
<script src="{{ asset('js/supplier-qr-autofill.js') }}" defer></script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#173d70">
    <title>Masuk · Stock Tracking Line AIJ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
@php
    $demoAccounts = [
        ['role' => 'Staf Administrasi', 'username' => 'admin', 'initial' => 'SA'],
        ['role' => 'Operator Warehouse', 'username' => 'warehouse', 'initial' => 'WH'],
        ['role' => 'Dandori', 'username' => 'dandori', 'initial' => 'DN'],
        ['role' => 'Operator Assembly', 'username' => 'assembly', 'initial' => 'AS'],
        ['role' => 'Operator Delivery', 'username' => 'delivery', 'initial' => 'DV'],
        ['role' => 'Atasan', 'username' => 'atasan', 'initial' => 'AT'],
    ];
@endphp
<div class="login-page">
    <section class="login-intro" aria-label="Informasi sistem">
        <header class="login-brand-row">
            <div class="brand-mark login-brand-mark">ST</div>
            <div class="login-brand-copy">
                <strong>Stock Tracking</strong>
                <span>LINE AIJ <i></i> PT. XYZ</span>
            </div>
            <span class="prototype-label"><span class="status-dot"></span> PROTOTYPE</span>
        </header>

        <div class="login-intro-copy">
            <p class="eyebrow">MATERIAL CONTROL / LINE AIJ</p>
            <h1>Setiap perpindahan<br>punya <span>jejak.</span></h1>
            <p class="login-lead">Pencatatan material yang lebih tertata, dengan informasi stok dan riwayat perpindahan dalam satu alur.</p>
        </div>

        <div class="process-board" aria-label="Lima tahap aliran material">
            <div class="process-board-head">
                <div>
                    <span class="micro-label">ALIRAN MATERIAL</span>
                    <strong>Line AIJ</strong>
                </div>
                <span class="process-count">05 TAHAP</span>
            </div>
            <div class="process-track">
                <div class="process-step"><span class="process-node">01</span><span>Incoming<br>Warehouse</span></div>
                <span class="process-connector"></span>
                <div class="process-step"><span class="process-node">02</span><span>Incoming<br>Rack</span></div>
                <span class="process-connector"></span>
                <div class="process-step"><span class="process-node">03</span><span>Dandori</span></div>
                <span class="process-connector"></span>
                <div class="process-step"><span class="process-node">04</span><span>Assembly</span></div>
                <span class="process-connector"></span>
                <div class="process-step"><span class="process-node">05</span><span>Delivery</span></div>
            </div>
        </div>

        <div class="login-benefits">
            <div class="benefit-item"><span class="benefit-check">✓</span><span>Catat dengan barcode</span></div>
            <div class="benefit-item"><span class="benefit-check">✓</span><span>Pantau posisi material</span></div>
            <div class="benefit-item"><span class="benefit-check">✓</span><span>Telusuri selisih stok</span></div>
        </div>

        <footer class="login-intro-footer">
            <span>CAPSTONE DESIGN PROJECT</span>
            <span>INVENTORY ACCURACY <i></i> TRACEABILITY</span>
        </footer>
    </section>

    <main class="login-form-wrap">
        <section class="login-card" aria-labelledby="login-title">
            <div class="login-card-heading">
                <div class="login-mobile-brand"><span class="brand-mark login-brand-mark">ST</span><span>Stock Tracking <small>Line AIJ · PT. XYZ</small></span></div>
                <span class="login-step-label">AKSES PENGGUNA</span>
                <h2 id="login-title">Selamat datang kembali</h2>
                <p>Masuk menggunakan akun sesuai peran kamu.</p>
            </div>

            @if (session('status'))
                <div class="alert success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="login-form">
                @csrf
                <div class="field login-field">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <span class="input-leading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.6-3.3 3.1-5 7-5s6.4 1.7 7 5"></path></svg>
                        </span>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Contoh: warehouse" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="field login-field">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <span class="input-leading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                        </span>
                        <input id="password" name="password" type="password" placeholder="Masukkan password" required autocomplete="current-password">
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan password" title="Tampilkan/sembunyikan password">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                        </button>
                    </div>
                </div>

                <button class="btn login-submit" type="submit">
                    <span>Masuk ke Sistem</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </button>
                <p class="login-form-hint"><span class="secure-mark">✓</span> Akses menu menyesuaikan peran pengguna.</p>
            </form>

            <section class="demo-access" aria-labelledby="demo-title">
                <div class="demo-heading-row">
                    <div>
                        <span class="login-step-label">UNTUK PENGUJIAN</span>
                        <h3 id="demo-title">Akun demo</h3>
                    </div>
                    <span class="demo-shared-label">Password sama</span>
                </div>
                <div class="demo-user-grid">
                    @foreach ($demoAccounts as $account)
                        <button
                            type="button"
                            class="demo-user"
                            data-demo-user="{{ $account['username'] }}"
                            data-demo-password="Capstone123!"
                            aria-pressed="false"
                        >
                            <span class="demo-avatar">{{ $account['initial'] }}</span>
                            <span class="demo-user-copy"><strong>{{ $account['role'] }}</strong><small>{{ $account['username'] }}</small></span>
                            <span class="demo-select-mark" aria-hidden="true">↗</span>
                        </button>
                    @endforeach
                </div>
                <div class="demo-password-row">
                    <div><span class="password-caption">PASSWORD DEMO</span><code id="demo-password-value">Capstone123!</code></div>
                    <button type="button" class="copy-password" id="copy-demo-password" data-copy-text="Capstone123!" aria-label="Salin password demo" title="Salin password">Salin</button>
                </div>
            </section>

            <footer class="login-bottom-note">Prototype untuk evaluasi rancangan Capstone Design Project.</footer>
        </section>
    </main>
</div>
<script src="{{ asset('js/ui.js') }}" defer></script>
</body>
</html>

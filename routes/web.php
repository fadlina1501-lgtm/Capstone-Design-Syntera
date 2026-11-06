<?php

use App\Http\Controllers\AssemblyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DandoriController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MaterialMovementController;
use App\Http\Controllers\MiniCycleCountController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartNgController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\RemoteScannerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Phone scanner endpoints are public by design; the random token is short-lived and hashed at rest.
Route::get('/phone-scanner/{token}', [RemoteScannerController::class, 'phone'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('phone-scanner.show');
Route::post('/phone-scanner/{token}/scan', [RemoteScannerController::class, 'storeScan'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:60,1')
    ->name('phone-scanner.scan');
Route::post('/phone-scanner/{token}/submit', [RemoteScannerController::class, 'requestSubmit'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:10,1')
    ->name('phone-scanner.submit');
Route::get('/phone-scanner/{token}/status', [RemoteScannerController::class, 'phoneStatus'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:120,1')
    ->name('phone-scanner.status');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Remote scanning is paired from the authenticated laptop session.
    Route::post('/remote-scanner/sessions', [RemoteScannerController::class, 'create'])
        ->middleware('role:Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')
        ->name('remote-scanner.create');
    Route::get('/remote-scanner/sessions/{token}/poll', [RemoteScannerController::class, 'poll'])
        ->where('token', '[A-Za-z0-9]{48}')
        ->middleware('role:Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')
        ->name('remote-scanner.poll');
    Route::post('/remote-scanner/sessions/{token}/submit-result', [RemoteScannerController::class, 'reportSubmitResult'])
        ->where('token', '[A-Za-z0-9]{48}')
        ->middleware('role:Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')
        ->name('remote-scanner.submit-result');
    Route::post('/remote-scanner/sessions/{token}/close', [RemoteScannerController::class, 'close'])
        ->where('token', '[A-Za-z0-9]{48}')
        ->middleware('role:Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')
        ->name('remote-scanner.close');

    // Master Data: Admin can create/update/delete; Atasan is read-only.
    Route::middleware('role:Staf Administrasi|Atasan')->prefix('master')->name('master.')->group(function () {
        Route::get('/{entity}', [MasterDataController::class, 'index'])->name('index');
        Route::get('/{entity}/create', [MasterDataController::class, 'create'])->middleware('role:Staf Administrasi')->name('create');
        Route::get('/{entity}/{id}/edit', [MasterDataController::class, 'edit'])->whereNumber('id')->middleware('role:Staf Administrasi')->name('edit');
        Route::post('/{entity}', [MasterDataController::class, 'store'])->middleware('role:Staf Administrasi')->name('store');
        Route::put('/{entity}/{id}', [MasterDataController::class, 'update'])->whereNumber('id')->middleware('role:Staf Administrasi')->name('update');
        Route::delete('/{entity}/{id}', [MasterDataController::class, 'destroy'])->whereNumber('id')->middleware('role:Staf Administrasi')->name('destroy');
    });

    // Data Transaksi: Admin and Atasan may inspect records; operational roles perform the transaction.
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [MaterialMovementController::class, 'index'])
            ->middleware('role:Staf Administrasi|Atasan')
            ->name('index');

        Route::middleware('role:Staf Administrasi|Atasan|Operator Warehouse')->group(function () {
            Route::get('/incoming-warehouse', [MaterialMovementController::class, 'incomingWarehouse'])->name('incoming-warehouse');
            Route::get('/incoming-rack', [MaterialMovementController::class, 'incomingRack'])->name('incoming-rack');
        });
        Route::middleware('role:Operator Warehouse')->group(function () {
            Route::post('/incoming-warehouse', [MaterialMovementController::class, 'storeIncomingWarehouse'])->name('incoming-warehouse.store');
            Route::post('/incoming-rack', [MaterialMovementController::class, 'storeIncomingRack'])->name('incoming-rack.store');
        });

        Route::middleware('role:Staf Administrasi|Atasan|Dandori')->group(function () {
            Route::get('/dandori', [DandoriController::class, 'index'])->name('dandori');
            Route::get('/dandori/{id}/tag', [DandoriController::class, 'printTag'])->whereNumber('id')->name('dandori.tag');
            Route::get('/dandori/box/{id}/tag', [DandoriController::class, 'printBoxTag'])->whereNumber('id')->name('dandori.box-tag');
        });
        Route::post('/dandori', [DandoriController::class, 'store'])->middleware('role:Dandori')->name('dandori.store');

        Route::middleware('role:Staf Administrasi|Atasan|Operator Assembly')->group(function () {
            Route::get('/assembly', [AssemblyController::class, 'index'])->name('assembly');
            Route::get('/part-ng', [PartNgController::class, 'index'])->name('part-ng');
        });
        Route::middleware('role:Operator Assembly')->group(function () {
            Route::post('/assembly', [AssemblyController::class, 'store'])->name('assembly.store');
            Route::post('/part-ng', [PartNgController::class, 'store'])->name('part-ng.store');
        });

        Route::middleware('role:Staf Administrasi|Atasan|Operator Delivery')->get('/delivery', [MaterialMovementController::class, 'delivery'])->name('delivery');
        Route::middleware('role:Operator Delivery')->group(function () {
            Route::post('/delivery', [MaterialMovementController::class, 'storeDelivery'])->name('delivery.store');
            Route::post('/delivery/loading', [MaterialMovementController::class, 'storeLoading'])->name('delivery.loading');
        });
    });

    // Operational roles consult the plan before Dandori, Assembly, and Delivery.
    Route::get('/production-plans', [ProductionPlanController::class, 'index'])
        ->middleware('role:Dandori|Operator Assembly|Operator Delivery')
        ->name('production-plans.index');

    // Inventory visibility follows the six role Use Cases.
    // Stock Monitoring and Stock Movement are available to all six roles.
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::middleware('role:Staf Administrasi|Atasan|Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')->group(function () {
            Route::get('/stocks', [MonitoringController::class, 'stocks'])->name('stocks');
            Route::get('/movements', [MonitoringController::class, 'movements'])->name('movements');
        });
        // Discrepancy is not part of the Dandori Use Case.
        Route::get('/discrepancies', [MonitoringController::class, 'discrepancies'])
            ->middleware('role:Staf Administrasi|Atasan|Operator Warehouse|Operator Assembly|Operator Delivery')
            ->name('discrepancies');
        Route::get('/production', [MonitoringController::class, 'production'])
            ->middleware('role:Staf Administrasi|Atasan')
            ->name('production');
    });

    // Stock Opname: Staf Administrasi manages and revises records; Atasan inspects
    // records and can request a correction without directly changing operational data.
    Route::middleware('role:Staf Administrasi|Atasan')->prefix('stock-opnames')->name('stock-opnames.')->group(function () {
        Route::get('/', [StockOpnameController::class, 'index'])->name('index');
        Route::get('/{id}', [StockOpnameController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/request-revision', [StockOpnameController::class, 'requestRevision'])
            ->whereNumber('id')->middleware('role:Atasan')->name('request-revision');
    });
    Route::middleware('role:Staf Administrasi')->prefix('stock-opnames')->name('stock-opnames.')->group(function () {
        Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
        Route::post('/', [StockOpnameController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [StockOpnameController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [StockOpnameController::class, 'update'])->whereNumber('id')->name('update');
    });

    // Mini Cycle Count: operational roles record counts; Atasan reviews, revises when needed, then approves.
    Route::middleware('role:Staf Administrasi|Operator Warehouse|Operator Assembly|Operator Delivery|Atasan')
        ->prefix('mini-cycle-counts')->name('mini-cycle-counts.')->group(function () {
            Route::get('/', [MiniCycleCountController::class, 'index'])->name('index');
            Route::get('/{id}', [MiniCycleCountController::class, 'show'])->whereNumber('id')->name('show');
        });
    Route::middleware('role:Staf Administrasi|Operator Warehouse|Operator Assembly|Operator Delivery')
        ->prefix('mini-cycle-counts')->name('mini-cycle-counts.')->group(function () {
            Route::get('/create', [MiniCycleCountController::class, 'create'])->name('create');
            Route::post('/', [MiniCycleCountController::class, 'store'])->name('store');
        });
    Route::middleware('role:Atasan')->prefix('mini-cycle-counts')->name('mini-cycle-counts.')->group(function () {
        Route::post('/{id}/revise', [MiniCycleCountController::class, 'revise'])->whereNumber('id')->name('revise');
        Route::post('/{id}/approve', [MiniCycleCountController::class, 'approve'])->whereNumber('id')->name('approve');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id')->name('notifications.read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');

    Route::middleware('role:Staf Administrasi|Atasan')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export/{type}', [ReportController::class, 'export'])
            ->whereIn('type', ['stocks', 'movements', 'discrepancies', 'production'])
            ->name('export');
    });
});

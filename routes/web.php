<?php

use App\Http\Controllers\AssemblyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DandoriController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MaterialMovementController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartNgController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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

    // Stock Opname is performed by each operational area. Admin can inspect all results,
    // while Atasan can approve a submission or request a recount.
    Route::middleware('role:Staf Administrasi|Atasan|Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')
        ->prefix('stock-opnames')->name('stock-opnames.')->group(function () {
            Route::get('/', [StockOpnameController::class, 'index'])->name('index');
            Route::get('/{id}', [StockOpnameController::class, 'show'])->whereNumber('id')->name('show');
        });

    Route::middleware('role:Operator Warehouse|Dandori|Operator Assembly|Operator Delivery')
        ->prefix('stock-opnames')->name('stock-opnames.')->group(function () {
            Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
            Route::post('/', [StockOpnameController::class, 'store'])->name('store');
        });

    Route::middleware('role:Atasan')->prefix('stock-opnames')->name('stock-opnames.')->group(function () {
        Route::post('/{id}/approve', [StockOpnameController::class, 'approve'])
            ->whereNumber('id')->name('approve');
        Route::post('/{id}/request-recount', [StockOpnameController::class, 'requestRecount'])
            ->whereNumber('id')->name('request-recount');
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

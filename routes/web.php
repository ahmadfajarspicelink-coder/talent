<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\LogdownInstallController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BastController;
use App\Http\Controllers\OrderDocumentController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\LogdownController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Route per modul dengan kontrol akses berbasis role (R2)
|--------------------------------------------------------------------------
| Setiap group dilindungi 'auth' (R1.4, hanya pengguna login) + middleware
| 'module:{modul}' (EnsureModuleAccess) yang memblokir modul terlarang per
| role (R2.2..R2.5). Karena tiap group memakai parameter modulnya sendiri,
| permintaan multi-modul ditangani per-route: modul yang diizinkan tetap
| jalan, modul terlarang diblokir diam-diam (R2.5).
*/

// Modul_Partner — admin & staff (R2.2, R2.3).
Route::middleware(['auth', 'module:partner'])->group(function () {
    Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
    Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
    Route::match(['put', 'patch'], '/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
    Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
});

// Modul_Client — admin only (R2.4). Read-only: client terbentuk dari Order
// dan baru tampil setelah Order-nya Complete (tidak ada tambah/edit manual).
Route::middleware(['auth', 'module:client'])->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients/{client}/upgrade', [ClientController::class, 'upgrade'])->name('clients.upgrade');
    Route::post('/clients/{client}/dismantle', [ClientController::class, 'dismantle'])->name('clients.dismantle');
});

// Modul_Package (Paket Internet) — admin only. Master data rujukan Order.
Route::middleware(['auth', 'module:package'])->group(function () {
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::match(['put', 'patch'], '/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
});

// Modul_Order — admin & staff (R2.2, R2.3).
Route::middleware(['auth', 'module:order'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/advance', [OrderController::class, 'advanceStatus'])->name('orders.advanceStatus');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders/{order}/documents', [OrderDocumentController::class, 'store'])->name('orders.documents.store');
    Route::delete('/orders/{order}/histories/{history}/document', [OrderDocumentController::class, 'destroy'])->name('orders.documents.destroy');
    Route::get('/orders/{order}/histories/{history}/document', [OrderDocumentController::class, 'preview'])->name('orders.documents.preview');
    Route::get('/orders/{order}/histories/{history}/document/raw', [OrderDocumentController::class, 'raw'])->name('orders.documents.raw');

    // BAST Module
    Route::post('/orders/{order}/bast/generate', [BastController::class, 'generate'])->name('orders.bast.generate');
    Route::get('/orders/{order}/bast/{bastDocument}/download', [BastController::class, 'download'])->name('orders.bast.download');
    Route::get('/orders/{order}/bast/download-all', [BastController::class, 'downloadAll'])->name('orders.bast.download-all');
});

// Modul_Finance — admin only (R2.4).
Route::middleware(['auth', 'module:finance'])->group(function () {
    Route::get('/finance/orders', [FinanceController::class, 'orderReport'])->name('finance.orders');
    Route::get('/finance/clients', [FinanceController::class, 'clientReport'])->name('finance.clients');
});

// Modul_User_Management — admin only (R2.4).
Route::middleware(['auth', 'module:user_management'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Modul_Network — admin & staff (SNMP monitoring)
Route::middleware(['auth', 'module:network'])->group(function () {
    Route::get('/network', \App\Livewire\Dashboard::class)->name('network.index');
    Route::get('/network/top-traffic', \App\Livewire\TopTraffic::class)->name('network.top-traffic');
    Route::get('/network/devices', \App\Livewire\DeviceManager::class)->name('network.devices');
    Route::get('/network/devices/{id}', \App\Livewire\DeviceDetail::class)->name('network.device-detail');
});

// Modul_Ticket → sub-modul Logdown — admin & staff.
// Mencatat downtime client aktif: vendor, waktu down, waktu up, durasi,
// reason, dan action. Durasi dihitung otomatis dari selisih waktu.
Route::middleware(['auth', 'module:ticket'])->group(function () {
    Route::get('/tickets/logdown', [LogdownController::class, 'index'])->name('logdown.index');
    Route::post('/tickets/logdown', [LogdownController::class, 'store'])->name('logdown.store');
    Route::match(['put', 'patch'], '/tickets/logdown/{logdown}', [LogdownController::class, 'update'])->name('logdown.update');
    Route::post('/tickets/logdown/{logdown}/resolve', [LogdownController::class, 'resolve'])->name('logdown.resolve');
    Route::delete('/tickets/logdown/{logdown}', [LogdownController::class, 'destroy'])->name('logdown.destroy');

    // Endpoint one-time: jalankan migration tabel downtime_logs via browser
    // (pengganti `php artisan migrate` bila PHP CLI tidak tersedia di
    // terminal). Hanya admin. Aman dipanggil berulang — Schema::create
    // di-skip bila tabel sudah ada.
    Route::get('/tickets/install', [LogdownInstallController::class, 'install'])
        ->name('logdown.install');
});

require __DIR__.'/auth.php';

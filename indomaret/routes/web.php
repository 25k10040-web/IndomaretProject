<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\AdminController;

// Root redirect
Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->role) {
            'ukasir' => redirect()->route('pos.index'),
            'ugudang' => redirect()->route('gudang.index'),
            'uadmin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('login'),
        };
    }
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/quick-login/{role}', [AuthController::class, 'quickLogin'])->name('quick.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Kasir (UKasir) - Modul POS Desktop
Route::middleware(['auth', 'role:ukasir'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::get('/search', [PosController::class, 'searchProduct'])->name('search');
    Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    Route::get('/receipt/{id}', [PosController::class, 'printReceipt'])->name('receipt');
});

// Gudang (UGudang) - Modul Gudang & Inventaris
Route::middleware(['auth', 'role:ugudang'])->prefix('gudang')->name('gudang.')->group(function () {
    Route::get('/', [GudangController::class, 'index'])->name('index');
    Route::post('/product', [GudangController::class, 'storeProduct'])->name('product.store');
    Route::put('/product/{id}', [GudangController::class, 'updateProduct'])->name('product.update');
    Route::delete('/product/{id}', [GudangController::class, 'destroyProduct'])->name('product.destroy');
    Route::post('/inbound', [GudangController::class, 'inbound'])->name('inbound');
    Route::post('/correction', [GudangController::class, 'correction'])->name('correction');
    Route::get('/movements', [GudangController::class, 'movements'])->name('movements');
});

// Admin (UAdmin) - Modul Laporan & Analitik (Mobile Web)
Route::middleware(['auth', 'role:uadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/sales', [AdminController::class, 'salesReport'])->name('sales');
    Route::get('/profit', [AdminController::class, 'profitReport'])->name('profit');
    Route::get('/export-csv', [AdminController::class, 'exportCsv'])->name('export.csv');
});

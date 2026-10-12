<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->homeRoute())
    : redirect()->route('login'));

Route::middleware('auth')->group(function () {
    // Shared (admin + kasir)
    Route::get('/kasir', [PosController::class, 'index'])->name('pos.index');
    Route::post('/kasir/checkout', [PosController::class, 'store'])->name('pos.checkout');
    // Registered before the admin resource; whereNumber keeps /produk/create out of products.show.
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
    Route::get('/produk/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
    Route::get('/transaksi', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/transaksi/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->name('sales.show');
    Route::get('/transaksi/{sale}/struk', [SaleController::class, 'receipt'])->whereNumber('sale')->name('sales.receipt');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('kategori', CategoryController::class)->except('show')->names('categories')->parameters(['kategori' => 'category']);
        Route::resource('produk', ProductController::class)->except(['index', 'show'])->names('products')->parameters(['produk' => 'product']);

        Route::get('/stok', [StockMovementController::class, 'index'])->name('stock.index');
        Route::get('/stok/masuk', [StockMovementController::class, 'createIn'])->name('stock.in.create');
        Route::post('/stok/masuk', [StockMovementController::class, 'storeIn'])->name('stock.in.store');
        Route::get('/stok/penyesuaian', [StockMovementController::class, 'createAdjustment'])->name('stock.adjustment.create');
        Route::post('/stok/penyesuaian', [StockMovementController::class, 'storeAdjustment'])->name('stock.adjustment.store');

        Route::post('/transaksi/{sale}/void', [SaleController::class, 'void'])->whereNumber('sale')->name('sales.void');

        Route::resource('pengguna', UserController::class)->except('show')->names('users')->parameters(['pengguna' => 'user']);
        Route::patch('/pengguna/{user}/status', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/laporan/csv', [ReportController::class, 'csv'])->name('reports.csv');

        Route::get('/pengaturan', [StoreSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [StoreSettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminStockController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerReportController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Toko (pelanggan, tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/', [ShopController::class, 'catalog'])->name('shop.catalog');
Route::get('/produk/{slug}', [ShopController::class, 'product'])->name('shop.product');
Route::get('/keranjang', [ShopController::class, 'cart'])->name('shop.cart');
Route::post('/keranjang', [ShopController::class, 'addToCart'])->name('shop.cart.add');
Route::delete('/keranjang/{id}', [ShopController::class, 'removeFromCart'])->name('shop.cart.remove');
Route::post('/pesanan', [ShopController::class, 'storeOrder'])->name('shop.order.store');
Route::get('/pesanan/{number}/bayar', [ShopController::class, 'payment'])->name('shop.payment');

/*
|--------------------------------------------------------------------------
| Autentikasi Admin & Pemilik Usaha
|--------------------------------------------------------------------------
*/
Route::get('/admin/masuk', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/masuk', [AuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Panel Admin Toko (role: admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/produk', [AdminProductController::class, 'index'])->name('admin.products');
    Route::post('/produk', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::put('/produk/{id}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/produk/{id}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');

    Route::get('/stok-masuk', [AdminStockController::class, 'index'])->name('admin.stock');
    Route::post('/stok-masuk', [AdminStockController::class, 'store'])->name('admin.stock.store');
    Route::post('/stok-masuk/{code}/prioritas', [AdminStockController::class, 'prioritize'])->name('admin.stock.priority');

    Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('admin.orders');
    Route::post('/pesanan/{number}', [AdminOrderController::class, 'update'])->name('admin.orders.update');
});

/*
|--------------------------------------------------------------------------
| Rekap Penjualan (role: owner atau admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:owner,admin'])->group(function (): void {
    Route::get('/rekap', [OwnerReportController::class, 'index'])->name('owner.report');
    Route::get('/rekap/unduh/{format}', [OwnerReportController::class, 'export'])
        ->whereIn('format', ['pdf', 'excel'])
        ->name('owner.export');
});

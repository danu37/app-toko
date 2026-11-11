<?php

use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public Storefront Routes
Route::get('/', [StoreController::class, 'index'])->name('home');
Route::get('/shop', [StoreController::class, 'shop'])->name('store.shop');
Route::get('/shop/{produk:slug}', [StoreController::class, 'show'])->name('store.product');

// Authenticated Operational Routes (Admin & Kasir)
Route::middleware(['auth', 'role:admin,kasir'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Produk: Admin & Kasir dapat melihat dan menambah produk
    Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
    Route::get('/produk/create', [ProdukController::class, 'create'])->name('produk.create');
    Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
    Route::get('/produk/{produk}', [ProdukController::class, 'show'])
        ->whereNumber('produk')
        ->name('produk.show');

    // Pelanggan: Admin & Kasir dapat melihat detail, daftar, dan menambah pelanggan
    Route::get('/pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
    Route::get('/pelanggan/create', [PelangganController::class, 'create'])->name('pelanggan.create');
    Route::post('/pelanggan', [PelangganController::class, 'store'])->name('pelanggan.store');
    Route::get('/pelanggan/{pelanggan}', [PelangganController::class, 'show'])
        ->whereNumber('pelanggan')
        ->name('pelanggan.show');

    // Transaksi: Admin & Kasir dapat melihat daftar, riwayat, detail, dan membuat transaksi
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/riwayat', [TransaksiController::class, 'riwayat'])->name('transaksi.riwayat');
    Route::get('/transaksi/create', [TransaksiController::class, 'create'])->name('transaksi.create');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])
        ->whereNumber('transaksi')
        ->name('transaksi.show');
    Route::get('/transaksi/{transaksi}/nota', [TransaksiController::class, 'nota'])
        ->whereNumber('transaksi')
        ->name('transaksi.nota');

    // Referensi Kategori & Cabang (read-only untuk kasir)
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
});

// Admin-Only Routes (Kasir wajib ditolak HTTP 403)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Produk: Edit & Hapus khusus Admin
    Route::get('/produk/{produk}/edit', [ProdukController::class, 'edit'])->name('produk.edit');
    Route::put('/produk/{produk}', [ProdukController::class, 'update'])->name('produk.update');
    Route::patch('/produk/{produk}', [ProdukController::class, 'update']);
    Route::delete('/produk/{produk}', [ProdukController::class, 'destroy'])->name('produk.destroy');

    // Pelanggan: Edit & Hapus khusus Admin
    Route::get('/pelanggan/{pelanggan}/edit', [PelangganController::class, 'edit'])->name('pelanggan.edit');
    Route::put('/pelanggan/{pelanggan}', [PelangganController::class, 'update'])->name('pelanggan.update');
    Route::patch('/pelanggan/{pelanggan}', [PelangganController::class, 'update']);
    Route::delete('/pelanggan/{pelanggan}', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');

    // Transaksi: Hapus/manage khusus Admin
    Route::delete('/transaksi/{transaksi}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');

    // Kategori CRUD khusus Admin
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::patch('/kategori/{kategori}', [KategoriController::class, 'update']);
    Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    // Cabang CRUD khusus Admin
    Route::get('/cabang/create', [CabangController::class, 'create'])->name('cabang.create');
    Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
    Route::get('/cabang/{cabang}/edit', [CabangController::class, 'edit'])->name('cabang.edit');
    Route::put('/cabang/{cabang}', [CabangController::class, 'update'])->name('cabang.update');
    Route::patch('/cabang/{cabang}', [CabangController::class, 'update']);
    Route::delete('/cabang/{cabang}', [CabangController::class, 'destroy'])->name('cabang.destroy');

    // Laporan Penjualan, Transaksi, Produk Terlaris, Rekap Pendapatan, Pelanggan & Export CSV khusus Admin
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/transaksi', [LaporanController::class, 'transaksi'])->name('laporan.transaksi');
    Route::get('/laporan/produk', [LaporanController::class, 'produk'])->name('laporan.produk');
    Route::get('/laporan/pelanggan', [LaporanController::class, 'pelanggan'])->name('laporan.pelanggan');
    Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');

    // Manajemen Pengguna & Pengaturan Role khusus Admin
    Route::resource('pengguna', UserController::class)
        ->parameters(['pengguna' => 'pengguna'])
        ->except(['show']);
    Route::patch('/pengguna/{pengguna}/role', [UserController::class, 'updateRole'])->name('pengguna.role');

    // Pengaturan Aplikasi khusus Admin
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
});

Route::middleware(['auth', 'role:admin,kasir'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

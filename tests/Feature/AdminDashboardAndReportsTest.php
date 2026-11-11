<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_returns_real_metrics_chart_top_products_and_stock_summary(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Pusat']);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $kategori = Kategori::factory()->create(['nama_kategori' => 'Sembako']);

        $beras = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => 'Beras Premium',
            'harga' => 75000,
            'stok' => 3, // menipis
            'is_active' => true,
        ]);

        $minyak = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => 'Minyak Sawit',
            'harga' => 25000,
            'stok' => 0, // habis
            'is_active' => true,
        ]);

        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Budi Santoso']);

        $trx1 = Transaksi::factory()->create([
            'tanggal' => '2026-10-10',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 175000,
            'metode_pembayaran' => 'tunai',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trx1->id_transaksi,
            'id_produk' => $beras->id_produk,
            'nama_produk' => $beras->nama_produk,
            'jumlah' => 2,
            'harga' => 75000,
        ]);

        // Legacy row tanpa id_produk namun nama sama
        DetailTransaksi::factory()->create([
            'id_transaksi' => $trx1->id_transaksi,
            'id_produk' => null,
            'nama_produk' => 'Minyak Sawit',
            'jumlah' => 1,
            'harga' => 25000,
        ]);

        // Transaksi dibatalkan harus dikecualikan dari omzet dan topProducts
        $trxBatal = Transaksi::factory()->create([
            'tanggal' => '2026-10-11',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 999000,
            'status' => Transaksi::STATUS_DIBATALKAN,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxBatal->id_transaksi,
            'id_produk' => $minyak->id_produk,
            'nama_produk' => $minyak->nama_produk,
            'jumlah' => 50,
            'harga' => 25000,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('summary.total_produk', 2)
                ->where('summary.total_pelanggan', 1)
                ->where('summary.total_transaksi', 2)
                ->where('summary.total_transaksi_selesai', 1)
                ->where('summary.total_omzet', 175000)
                ->has('salesChart.daily', 1)
                ->where('salesChart.daily.0.total_pendapatan', 175000)
                ->has('salesChart.monthly', 1)
                ->has('topProducts', 2)
                ->where('topProducts.0.nama_produk', 'Beras Premium')
                ->where('topProducts.0.total_terjual', 2)
                ->where('stockSummary.total_sku', 2)
                ->where('stockSummary.total_unit', 3)
                ->where('stockSummary.stok_menipis', 1)
                ->where('stockSummary.stok_habis', 1)
                ->has('stockSummary.low_stock_products', 2)
            );

        // Kasir tidak boleh menerima analitik admin
        $this->actingAs($kasir)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->missing('salesChart')
                ->missing('topProducts')
                ->missing('stockSummary')
                ->missing('summary.total_omzet')
            );
    }

    public function test_admin_can_view_sales_and_transaction_reports_filtered_by_date_branch_and_customer(): void
    {
        $cabangA = Cabang::factory()->create(['nama_cabang' => 'Cabang A']);
        $cabangB = Cabang::factory()->create(['nama_cabang' => 'Cabang B']);
        $admin = User::factory()->admin()->create();
        $pelangganA = Pelanggan::factory()->create(['nama_pelanggan' => 'Andi']);
        $pelangganB = Pelanggan::factory()->create(['nama_pelanggan' => 'Budi']);

        Transaksi::factory()->create([
            'tanggal' => '2026-10-05',
            'id_pelanggan' => $pelangganA->id_pelanggan,
            'id_cabang' => $cabangA->id_cabang,
            'total_belanja' => 200000,
            'metode_pembayaran' => 'qris',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        Transaksi::factory()->create([
            'tanggal' => '2026-10-15',
            'id_pelanggan' => $pelangganB->id_pelanggan,
            'id_cabang' => $cabangB->id_cabang,
            'total_belanja' => 400000,
            'metode_pembayaran' => 'tunai',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        // Laporan penjualan dengan filter pelanggan + tanggal
        $this->actingAs($admin)
            ->get(route('laporan.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'pelanggan' => $pelangganA->id_pelanggan,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/Index')
                ->where('aggregates.total_transaksi', 1)
                ->where('aggregates.total_pendapatan', 200000)
                ->has('paymentMethodRecap', 1)
                ->where('paymentMethodRecap.0.metode', 'qris')
            );

        // Laporan transaksi filter pelanggan B + cabang B
        $this->actingAs($admin)
            ->get(route('laporan.transaksi', [
                'cabang' => $cabangB->id_cabang,
                'pelanggan' => $pelangganB->id_pelanggan,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/Transaksi')
                ->where('aggregates.total_omzet', 400000)
                ->has('transactions.data', 1)
            );
    }

    public function test_pelanggan_detail_and_transaksi_search_with_date_filter_and_history_work(): void
    {
        $cabang = Cabang::factory()->create();
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Citra Lestari']);

        $trxMatch = Transaksi::factory()->create([
            'kode_transaksi' => 'TRX-TARGET-01',
            'tanggal' => '2026-10-10',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 120000,
        ]);

        // Transaksi kode mirip tetapi tanggal berbeda (harus tersaring bila filter tanggal dipasang)
        $trxOtherDate = Transaksi::factory()->create([
            'kode_transaksi' => 'TRX-TARGET-02',
            'tanggal' => '2026-10-12',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 80000,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxMatch->id_transaksi,
            'nama_produk' => 'Kopi Spesial',
            'jumlah' => 2,
            'harga' => 60000,
        ]);

        $this->actingAs($admin)
            ->get(route('pelanggan.show', $pelanggan))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pelanggan/Show')
                ->where('customerSummary.total_transaksi', 2)
                ->where('customerSummary.total_belanja', 200000)
                ->has('transactions.data', 2)
            );

        // Search + Tanggal harus menerapkan AND precedence secara tepat
        $this->actingAs($admin)
            ->get(route('transaksi.index', [
                'search' => 'TRX-TARGET',
                'tanggal' => '2026-10-10',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Index')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id_transaksi', $trxMatch->id_transaksi)
            );

        // Riwayat transaksi dapat mencari berdasarkan nama produk detail
        $this->actingAs($admin)
            ->get(route('transaksi.riwayat', [
                'search' => 'Kopi Spesial',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Riwayat')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id_transaksi', $trxMatch->id_transaksi)
            );
    }

    public function test_admin_can_update_user_role_and_kasir_gets_403_and_self_demotion_is_blocked(): void
    {
        $cabang = Cabang::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->kasir()->create();
        $kasirActor = User::factory()->kasir()->create();

        // Kasir ditolak 403 saat memanggil endpoint pengaturan role langsung
        $this->actingAs($kasirActor)
            ->patch(route('pengguna.role', $staff), [
                'role' => 'admin',
            ])
            ->assertForbidden();

        // Admin berhasil mengubah role staff
        $this->actingAs($admin)
            ->patch(route('pengguna.role', $staff), [
                'role' => 'admin',
                'id_cabang' => $cabang->id_cabang,
                'is_active' => true,
            ])
            ->assertRedirect(route('pengguna.index'));

        $staff->refresh();
        $this->assertSame(User::ROLE_ADMIN, $staff->role);
        $this->assertSame($cabang->id_cabang, $staff->id_cabang);

        // Admin tidak boleh menurunkan role atau menonaktifkan dirinya sendiri
        $this->actingAs($admin)
            ->patch(route('pengguna.role', $admin), [
                'role' => 'kasir',
                'is_active' => false,
            ])
            ->assertSessionHasErrors(['role', 'is_active']);

        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_admin_sales_report_supports_cashier_filter_chart_cashier_recap_and_accurate_rekap(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Utama']);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasirA = User::factory()->kasir()->create(['name' => 'Kasir Alpha', 'id_cabang' => $cabang->id_cabang]);
        $kasirB = User::factory()->kasir()->create(['name' => 'Kasir Beta', 'id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Dewi Kartika']);

        $trxA1 = Transaksi::factory()->create([
            'tanggal' => '2026-09-12',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasirA->id,
            'total_belanja' => 150000,
            'metode_pembayaran' => 'tunai',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        $trxA2 = Transaksi::factory()->create([
            'tanggal' => '2026-10-05',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasirA->id,
            'total_belanja' => 250000,
            'metode_pembayaran' => 'qris',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        // Dibatalkan milik Kasir A: harus dikecualikan dari pendapatan
        Transaksi::factory()->create([
            'tanggal' => '2026-10-06',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasirA->id,
            'total_belanja' => 500000,
            'metode_pembayaran' => 'tunai',
            'status' => Transaksi::STATUS_DIBATALKAN,
        ]);

        $trxB1 = Transaksi::factory()->create([
            'tanggal' => '2026-10-10',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasirB->id,
            'total_belanja' => 100000,
            'metode_pembayaran' => 'transfer',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        $produkAlpha = Produk::factory()->create([
            'nama_produk' => 'Produk Alpha',
            'harga' => 50000,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxA1->id_transaksi,
            'id_produk' => $produkAlpha->id_produk,
            'nama_produk' => $produkAlpha->nama_produk,
            'jumlah' => 3,
            'harga' => 50000,
        ]);
        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxA2->id_transaksi,
            'id_produk' => $produkAlpha->id_produk,
            'nama_produk' => $produkAlpha->nama_produk,
            'jumlah' => 5,
            'harga' => 50000,
        ]);
        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxB1->id_transaksi,
            'id_produk' => null,
            'nama_produk' => 'Produk Beta',
            'jumlah' => 2,
            'harga' => 50000,
        ]);

        // Tanpa filter kasir: seluruh pendapatan & rekap kedua kasir tampil akurat
        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/Index')
                ->where('aggregates.total_transaksi', 3)
                ->where('aggregates.total_dibatalkan', 1)
                ->where('aggregates.total_pendapatan', 500000)
                ->where('aggregates.total_item_terjual', 10)
                ->has('salesChart.daily', 3)
                ->has('salesChart.monthly', 2)
                ->has('cashierRecap', 2)
                ->where('cashierRecap.0.nama_kasir', 'Kasir Alpha')
                ->where('cashierRecap.0.total_pendapatan', 400000)
                ->where('cashierRecap.1.nama_kasir', 'Kasir Beta')
                ->where('cashierRecap.1.total_pendapatan', 100000)
            );

        // Filter kasir = Kasir Alpha
        $this->actingAs($admin)
            ->get(route('laporan.index', ['kasir' => $kasirA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/Index')
                ->where('aggregates.total_transaksi', 2)
                ->where('aggregates.total_dibatalkan', 1)
                ->where('aggregates.total_pendapatan', 400000)
                ->where('aggregates.total_item_terjual', 8)
                ->has('cashierRecap', 1)
                ->where('cashierRecap.0.nama_kasir', 'Kasir Alpha')
                ->has('productSales.data', 1)
                ->where('productSales.data.0.nama_produk', 'Produk Alpha')
            );
    }

    public function test_admin_transaction_report_and_history_support_search_cashier_and_date_filters(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Timur']);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasirA = User::factory()->kasir()->create(['name' => 'Rani Kasir', 'id_cabang' => $cabang->id_cabang]);
        $kasirB = User::factory()->kasir()->create(['name' => 'Doni Kasir', 'id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Siti Rahma']);

        $trxRani = Transaksi::factory()->create([
            'kode_transaksi' => 'TRX-RANI-001',
            'tanggal' => '2026-10-15',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasirA->id,
            'total_belanja' => 180000,
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxRani->id_transaksi,
            'nama_produk' => 'Teh Melati Premium',
            'jumlah' => 6,
            'harga' => 30000,
        ]);

        $trxDoni = Transaksi::factory()->create([
            'kode_transaksi' => 'TRX-DONI-002',
            'tanggal' => '2026-10-16',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasirB->id,
            'total_belanja' => 90000,
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxDoni->id_transaksi,
            'nama_produk' => 'Gula Pasir 1kg',
            'jumlah' => 5,
            'harga' => 18000,
        ]);

        // Filter laporan transaksi berdasarkan kasir + pencarian nama item detail
        $this->actingAs($admin)
            ->get(route('laporan.transaksi', [
                'kasir' => $kasirA->id,
                'search' => 'Teh Melati',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/Transaksi')
                ->where('aggregates.total_transaksi', 1)
                ->where('aggregates.total_omzet', 180000)
                ->where('aggregates.total_item_terjual', 6)
                ->has('transactions.data', 1)
                ->where('transactions.data.0.kode_transaksi', 'TRX-RANI-001')
            );

        // Admin juga dapat memfilter Riwayat Transaksi berdasarkan kasir
        $this->actingAs($admin)
            ->get(route('transaksi.riwayat', ['kasir' => $kasirB->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Riwayat')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.kode_transaksi', 'TRX-DONI-002')
            );
    }

    public function test_admin_top_products_report_filters_by_category_cashier_sort_and_search(): void
    {
        $cabang = Cabang::factory()->create();
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create();

        $katMinuman = Kategori::factory()->create(['nama_kategori' => 'Minuman']);
        $katSembako = Kategori::factory()->create(['nama_kategori' => 'Sembako']);

        $airMineral = Produk::factory()->create([
            'id_kategori' => $katMinuman->id_kategori,
            'kode_produk' => 'MIN-01',
            'nama_produk' => 'Air Mineral',
            'harga' => 5000,
            'stok' => 50,
        ]);

        $beras = Produk::factory()->create([
            'id_kategori' => $katSembako->id_kategori,
            'kode_produk' => 'SMB-01',
            'nama_produk' => 'Beras Wangi 5kg',
            'harga' => 80000,
            'stok' => 10,
        ]);

        $trx = Transaksi::factory()->create([
            'tanggal' => '2026-10-20',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 210000,
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        // Qty terbanyak (10 unit), Omzet lebih kecil (50.000)
        DetailTransaksi::factory()->create([
            'id_transaksi' => $trx->id_transaksi,
            'id_produk' => $airMineral->id_produk,
            'nama_produk' => $airMineral->nama_produk,
            'jumlah' => 10,
            'harga' => 5000,
        ]);

        // Qty lebih sedikit (2 unit), Omzet tertinggi (160.000)
        DetailTransaksi::factory()->create([
            'id_transaksi' => $trx->id_transaksi,
            'id_produk' => $beras->id_produk,
            'nama_produk' => $beras->nama_produk,
            'jumlah' => 2,
            'harga' => 80000,
        ]);

        // Transaksi dibatalkan harus diabaikan
        $trxBatal = Transaksi::factory()->create([
            'tanggal' => '2026-10-20',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 800000,
            'status' => Transaksi::STATUS_DIBATALKAN,
        ]);
        DetailTransaksi::factory()->create([
            'id_transaksi' => $trxBatal->id_transaksi,
            'id_produk' => $beras->id_produk,
            'nama_produk' => $beras->nama_produk,
            'jumlah' => 10,
            'harga' => 80000,
        ]);

        // Default urut qty: Air Mineral #1
        $this->actingAs($admin)
            ->get(route('laporan.produk'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/ProdukTerlaris')
                ->where('aggregates.total_sku_terjual', 2)
                ->where('aggregates.total_unit_terjual', 12)
                ->where('aggregates.total_pendapatan', 210000)
                ->where('aggregates.top_product_name', 'Air Mineral')
                ->where('products.data.0.nama_produk', 'Air Mineral')
                ->where('products.data.1.nama_produk', 'Beras Wangi 5kg')
            );

        // Urut revenue + filter kategori Sembako
        $this->actingAs($admin)
            ->get(route('laporan.produk', [
                'sort' => 'revenue',
                'kategori' => $katSembako->id_kategori,
                'kasir' => $kasir->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Laporan/ProdukTerlaris')
                ->where('aggregates.total_sku_terjual', 1)
                ->where('aggregates.total_pendapatan', 160000)
                ->where('products.data.0.nama_produk', 'Beras Wangi 5kg')
            );
    }

    public function test_admin_can_export_filtered_reports_as_csv_and_neutralizes_formula_cells(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Pusat']);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasir = User::factory()->kasir()->create(['name' => 'Kasir Export', 'id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => '=SUM(1+1) Pelanggan']);

        $produk = Produk::factory()->create([
            'kode_produk' => 'EXP-01',
            'nama_produk' => '+Beras Formula',
            'harga' => 70000,
            'stok' => 15,
        ]);

        $trx = Transaksi::factory()->create([
            'kode_transaksi' => 'TRX-EXP-001',
            'tanggal' => '2026-10-25',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
            'total_belanja' => 140000,
            'metode_pembayaran' => 'tunai',
            'status' => Transaksi::STATUS_SELESAI,
        ]);

        DetailTransaksi::factory()->create([
            'id_transaksi' => $trx->id_transaksi,
            'id_produk' => $produk->id_produk,
            'nama_produk' => $produk->nama_produk,
            'jumlah' => 2,
            'harga' => 70000,
        ]);

        // Kasir harus tetap ditolak HTTP 403
        $this->actingAs($kasir)
            ->get(route('laporan.export', ['report' => 'transactions']))
            ->assertForbidden();

        // Export transactions CSV (Admin)
        $trxResponse = $this->actingAs($admin)
            ->get(route('laporan.export', ['report' => 'transactions', 'kasir' => $kasir->id]))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $trxResponse->headers->get('Content-Type'));
        $trxCsv = $trxResponse->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $trxCsv);
        $this->assertStringContainsString('TRX-EXP-001', $trxCsv);
        $this->assertStringContainsString("'=SUM(1+1) Pelanggan", $trxCsv);
        $this->assertStringContainsString('Kasir Export', $trxCsv);

        // Export products CSV (Admin)
        $prodCsv = $this->actingAs($admin)
            ->get(route('laporan.export', ['report' => 'products']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString("'+Beras Formula", $prodCsv);
        $this->assertStringContainsString('140000.00', $prodCsv);

        // Export sales daily recap CSV (Admin)
        $salesCsv = $this->actingAs($admin)
            ->get(route('laporan.export', ['report' => 'sales', 'start_date' => '2026-10-25', 'end_date' => '2026-10-25']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('2026-10-25', $salesCsv);
        $this->assertStringContainsString('140000.00', $salesCsv);

        // Export customers CSV (Admin)
        $custCsv = $this->actingAs($admin)
            ->get(route('laporan.export', ['report' => 'customers', 'start_date' => '2026-10-25', 'end_date' => '2026-10-25']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString("'=SUM(1+1) Pelanggan", $custCsv);
        $this->assertStringContainsString('140000.00', $custCsv);
    }
}

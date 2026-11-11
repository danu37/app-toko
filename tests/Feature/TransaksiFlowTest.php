<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransaksiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_create_transaction_and_backend_computes_authoritative_total(): void
    {
        $cabang = Cabang::factory()->create();
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create();

        $produkA = Produk::factory()->create(['nama_produk' => 'Produk A', 'harga' => 25000, 'stok' => 10]);
        $produkB = Produk::factory()->create(['nama_produk' => 'Produk B', 'harga' => 15000, 'stok' => 15]);

        $response = $this->actingAs($kasir)->post(route('transaksi.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'metode_pembayaran' => 'tunai',
            'uang_bayar' => 100000,
            // Nilai total/harga palsu dari client harus diabaikan backend
            'total_belanja' => 1,
            'kembalian' => 999999,
            'items' => [
                ['id_produk' => $produkA->id_produk, 'jumlah' => 2, 'harga' => 1],
                ['id_produk' => $produkB->id_produk, 'jumlah' => 3, 'harga' => 1],
            ],
        ]);

        $transaksi = Transaksi::query()->latest('id_transaksi')->firstOrFail();

        $response->assertRedirect(route('transaksi.show', $transaksi));
        $this->assertNotEmpty($transaksi->kode_transaksi);

        // 2 * 25000 + 3 * 15000 = 95000, uang_bayar = 100000 => kembalian = 5000
        $this->assertSame('95000.00', $transaksi->total_belanja);
        $this->assertSame('100000.00', $transaksi->uang_bayar);
        $this->assertSame('5000.00', $transaksi->kembalian);
        $this->assertSame($kasir->id, $transaksi->user_id);
        $this->assertSame($kasir->name, $transaksi->nama_kasir);
        $this->assertSame($cabang->id_cabang, $transaksi->id_cabang);

        // Stok otomatis berkurang secara atomik di database
        $this->assertSame(8, $produkA->fresh()->stok);
        $this->assertSame(12, $produkB->fresh()->stok);

        $this->assertDatabaseHas('tbl_detail_transaksi', [
            'id_transaksi' => $transaksi->id_transaksi,
            'id_produk' => $produkA->id_produk,
            'nama_produk' => 'Produk A',
            'jumlah' => 2,
            'harga' => 25000.00,
        ]);

        $this->assertDatabaseHas('tbl_detail_transaksi', [
            'id_transaksi' => $transaksi->id_transaksi,
            'id_produk' => $produkB->id_produk,
            'nama_produk' => 'Produk B',
            'jumlah' => 3,
            'harga' => 15000.00,
        ]);
    }

    public function test_old_transaction_snapshots_remain_unchanged_when_product_price_or_name_changes_later(): void
    {
        $kasir = User::factory()->kasir()->create();
        $pelanggan = Pelanggan::factory()->create();
        $produk = Produk::factory()->create([
            'nama_produk' => 'Kopi Awal',
            'harga' => 20000,
            'stok' => 20,
        ]);

        $this->actingAs($kasir)->post(route('transaksi.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'metode_pembayaran' => 'tunai',
            'uang_bayar' => 50000,
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 2],
            ],
        ])->assertRedirect();

        $transaksi = Transaksi::query()->with('detail')->latest('id_transaksi')->firstOrFail();

        // Harga & nama produk master berubah di kemudian hari
        $produk->update([
            'nama_produk' => 'Kopi Harga Baru',
            'harga' => 35000,
        ]);

        $transaksi = $transaksi->fresh('detail');

        $this->assertSame('40000.00', $transaksi->total_belanja);
        $this->assertSame('Kopi Awal', $transaksi->detail->first()->nama_produk);
        $this->assertSame('20000.00', $transaksi->detail->first()->harga);
        $this->assertSame(40000.0, $transaksi->detail->first()->subtotal);
    }

    public function test_transaction_fails_when_stock_is_insufficient_and_rolls_back_atomically(): void
    {
        $kasir = User::factory()->kasir()->create();
        $pelanggan = Pelanggan::factory()->create();

        $produkCukup = Produk::factory()->create(['nama_produk' => 'Beras', 'harga' => 50000, 'stok' => 5]);
        $produkKurang = Produk::factory()->create(['nama_produk' => 'Gula', 'harga' => 15000, 'stok' => 2]);

        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 500000,
                'items' => [
                    ['id_produk' => $produkCukup->id_produk, 'jumlah' => 2],
                    ['id_produk' => $produkKurang->id_produk, 'jumlah' => 3], // Melebihi stok (2)
                ],
            ])
            ->assertSessionHasErrors('items.1.jumlah');

        // Tidak boleh ada transaksi tersimpan sebagian ataupun stok produk lain ikut terpotong
        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertDatabaseCount('tbl_detail_transaksi', 0);
        $this->assertSame(5, $produkCukup->fresh()->stok);
        $this->assertSame(2, $produkKurang->fresh()->stok);
    }

    public function test_transaction_validation_rejects_invalid_payloads(): void
    {
        $kasir = User::factory()->kasir()->create();
        $pelanggan = Pelanggan::factory()->create();
        $produk = Produk::factory()->create(['harga' => 10000, 'stok' => 10]);

        // Tanpa pelanggan, keranjang kosong, dan tanpa nominal bayar tunai
        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'metode_pembayaran' => 'tunai',
                'items' => [],
            ])
            ->assertSessionHasErrors(['id_pelanggan', 'items', 'uang_bayar']);

        // Jumlah item 0 ditolak
        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 50000,
                'items' => [
                    ['id_produk' => $produk->id_produk, 'jumlah' => 0],
                ],
            ])
            ->assertSessionHasErrors(['items.0.jumlah']);

        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertDatabaseCount('tbl_detail_transaksi', 0);
        $this->assertSame(10, $produk->fresh()->stok);
    }

    public function test_transaction_rejects_inactive_product(): void
    {
        $kasir = User::factory()->kasir()->create();
        $pelanggan = Pelanggan::factory()->create();
        $produk = Produk::factory()->create(['is_active' => false, 'stok' => 10]);

        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 50000,
                'items' => [
                    ['id_produk' => $produk->id_produk, 'jumlah' => 1],
                ],
            ])
            ->assertSessionHasErrors(['items.0.id_produk']);

        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertDatabaseCount('tbl_detail_transaksi', 0);
        $this->assertSame(10, $produk->fresh()->stok);
    }

    public function test_kasir_cannot_delete_transaction(): void
    {
        $kasir = User::factory()->kasir()->create();
        $transaksi = Transaksi::factory()->create(['user_id' => $kasir->id]);

        $this->actingAs($kasir)
            ->delete(route('transaksi.destroy', $transaksi))
            ->assertForbidden();

        $this->assertDatabaseHas('tbl_transaksi', [
            'id_transaksi' => $transaksi->id_transaksi,
        ]);
    }

    public function test_cash_payment_computes_authoritative_change_and_rejects_underpayment(): void
    {
        $cabang = Cabang::factory()->create();
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create();
        $produk = Produk::factory()->create(['nama_produk' => 'Minyak 2L', 'harga' => 35000, 'stok' => 10]);

        // Kurang bayar wajib ditolak backend dan stok tidak berkurang
        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 50000,
                'items' => [
                    ['id_produk' => $produk->id_produk, 'jumlah' => 2], // total = 70.000
                ],
            ])
            ->assertSessionHasErrors('uang_bayar');

        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertSame(10, $produk->fresh()->stok);

        // Bayar valid menghitung kembalian otoritatif di server walaupun kembalian palsu dikirim
        $response = $this->actingAs($kasir)->post(route('transaksi.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'metode_pembayaran' => 'tunai',
            'uang_bayar' => 100000,
            'kembalian' => 999999,
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 2],
            ],
        ]);

        $trx = Transaksi::query()->latest('id_transaksi')->firstOrFail();
        $response->assertRedirect(route('transaksi.show', $trx));

        $this->assertSame('70000.00', $trx->total_belanja);
        $this->assertSame('100000.00', $trx->uang_bayar);
        $this->assertSame('30000.00', $trx->kembalian);
        $this->assertSame(8, $produk->fresh()->stok);

        // Non-tunai (QRIS) tanpa nominal uang_bayar mengisi lunas otomatis
        $this->actingAs($kasir)->post(route('transaksi.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'metode_pembayaran' => 'qris',
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 1],
            ],
        ])->assertRedirect();

        $qrisTrx = Transaksi::query()->latest('id_transaksi')->firstOrFail();
        $this->assertSame('35000.00', $qrisTrx->total_belanja);
        $this->assertSame('35000.00', $qrisTrx->uang_bayar);
        $this->assertSame('0.00', $qrisTrx->kembalian);
        $this->assertSame(7, $produk->fresh()->stok);
    }

    public function test_transaksi_show_renders_printable_receipt_payload(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Nota', 'kota' => 'Ponorogo']);
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Siti Nota']);
        $produk = Produk::factory()->create(['nama_produk' => 'Teh Celup', 'harga' => 8500, 'stok' => 12]);

        $this->actingAs($kasir)->post(route('transaksi.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'metode_pembayaran' => 'tunai',
            'uang_bayar' => 20000,
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 2],
            ],
        ])->assertRedirect();

        $trx = Transaksi::query()->latest('id_transaksi')->firstOrFail();

        $this->actingAs($kasir)
            ->get(route('transaksi.show', $trx))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Show')
                ->where('transaction.kode_transaksi', $trx->kode_transaksi)
                ->where('transaction.nama_kasir', $kasir->name)
                ->where('transaction.total_belanja', 17000)
                ->where('transaction.uang_bayar', 20000)
                ->where('transaction.kembalian', 3000)
                ->where('transaction.pelanggan.nama_pelanggan', 'Siti Nota')
                ->where('transaction.cabang.nama_cabang', 'Cabang Nota')
                ->where('transaction.detail.0.nama_produk', 'Teh Celup')
                ->where('transaction.detail.0.subtotal', 17000)
                ->where('storeInfo.name', 'TokoKita')
            );

        // Halaman dedicated Cetak Nota (/transaksi/{id}/nota) mendukung 40mm / 58mm / 80mm dengan fallback aman
        $this->actingAs($kasir)
            ->get(route('transaksi.nota', ['transaksi' => $trx->id_transaksi, 'size' => '80mm']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Receipt')
                ->where('initialSize', '80mm')
                ->where('transaction.kode_transaksi', $trx->kode_transaksi)
                ->where('transaction.nama_kasir', $kasir->name)
                ->where('storeInfo.name', 'TokoKita')
            );

        // Snapshot nama_kasir pada transaksi tetap utuh walaupun nama user kasir diubah atau dihapus
        $originalKasirName = $kasir->name;
        $kasir->update(['name' => 'Nama Kasir Sudah Berubah']);
        $admin = User::factory()->admin()->create(['name' => 'Admin Pencetak']);

        $this->actingAs($admin)
            ->get(route('transaksi.nota', ['transaksi' => $trx->id_transaksi, 'size' => '58mm']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Receipt')
                ->where('transaction.nama_kasir', $originalKasirName)
            );

        $kasirTerhapus = User::factory()->kasir()->create(['name' => 'Kasir Sementara']);
        $this->actingAs($kasirTerhapus)->post(route('transaksi.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'metode_pembayaran' => 'qris',
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 1],
            ],
        ])->assertRedirect();

        $trxHapusUser = Transaksi::query()->latest('id_transaksi')->firstOrFail();
        $kasirTerhapus->delete();

        $this->assertNull($trxHapusUser->fresh()->user_id);
        $this->actingAs($admin)
            ->get(route('transaksi.nota', $trxHapusUser))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Receipt')
                ->where('transaction.kasir', null)
                ->where('transaction.nama_kasir', 'Kasir Sementara')
            );

        // Transaksi lama (user_id null & nama_kasir null) menyediakan auth.user.name sebagai fallback cetak nota
        $legacyTrx = Transaksi::factory()->create([
            'kode_transaksi' => null,
            'user_id' => null,
            'nama_kasir' => null,
            'id_pelanggan' => $pelanggan->id_pelanggan,
        ]);

        $this->actingAs($admin)
            ->get(route('transaksi.nota', $legacyTrx))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Receipt')
                ->where('transaction.nama_kasir', null)
                ->where('auth.user.name', 'Admin Pencetak')
            );

        $this->actingAs($kasir)
            ->get(route('transaksi.nota', ['transaksi' => $trx->id_transaksi, 'size' => 'invalid']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Receipt')
                ->where('initialSize', '58mm')
            );

        $guest = User::factory()->create(['role' => 'unknown']);
        $this->actingAs($guest)
            ->get(route('transaksi.nota', $trx))
            ->assertForbidden();
    }

    public function test_quick_customer_creation_from_pos_redirects_back_with_customer_selected(): void
    {
        $kasir = User::factory()->kasir()->create();

        // Nilai 'from' di luar allowlist 'pos' ditolak validasi
        $this->actingAs($kasir)
            ->post(route('pelanggan.store'), [
                'nama_pelanggan' => 'Budi Santoso',
                'kota' => 'Malang',
                'from' => 'https://evil.example',
            ])
            ->assertSessionHasErrors('from');

        $response = $this->actingAs($kasir)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'Sari POS',
            'kota' => 'Surabaya',
            'from' => 'pos',
        ]);

        $pelanggan = Pelanggan::query()->where('nama_pelanggan', 'Sari POS')->firstOrFail();
        $response->assertRedirect(route('transaksi.create', ['customer' => $pelanggan->id_pelanggan]));
    }
}

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

class StorefrontAndRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_endpoints_work_for_guests_and_hide_inactive_products(): void
    {
        $branch = Cabang::factory()->create(['nama_cabang' => 'Cabang Jakarta', 'kota' => 'Jakarta']);
        $kategori = Kategori::factory()->create(['nama_kategori' => 'Sembako', 'slug' => 'sembako', 'is_active' => true]);
        $inactiveKategori = Kategori::factory()->create(['nama_kategori' => 'Arsip', 'slug' => 'arsip', 'is_active' => false]);

        $activeProduct = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => 'Beras Pilihan 5 Kg',
            'is_active' => true,
            'is_featured' => true,
        ]);
        $inactiveProduct = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Store/Index')
                ->has('categories', 1)
                ->where('categories.0.slug', 'sembako')
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.id_produk', $activeProduct->id_produk)
                ->has('branches', 1)
                ->where('branches.0.id_cabang', $branch->id_cabang)
                ->where('storeStats.total_products', 1)
            );

        $this->get(route('store.shop', ['kategori' => 'sembako']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Store/Shop')
                ->has('products.data', 1)
                ->where('filters.kategori', 'sembako')
            );

        $this->get(route('store.product', $activeProduct))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Store/ProductDetail')
                ->has('categories', 1)
                ->where('product.id_produk', $activeProduct->id_produk)
            );

        $this->get(route('store.product', $inactiveProduct))->assertNotFound();
    }

    public function test_all_eloquent_relations_resolve_properly(): void
    {
        $cabang = Cabang::factory()->create();
        $user = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);
        $pelanggan = Pelanggan::factory()->create();
        $transaksi = Transaksi::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $user->id,
        ]);
        $detail = DetailTransaksi::factory()->create([
            'id_transaksi' => $transaksi->id_transaksi,
            'id_produk' => $produk->id_produk,
            'nama_produk' => $produk->nama_produk,
            'jumlah' => 2,
            'harga' => 10000,
        ]);

        $this->assertTrue($user->cabang->is($cabang));
        $this->assertTrue($cabang->users->contains($user));
        $this->assertTrue($cabang->transaksi->contains($transaksi));
        $this->assertTrue($produk->kategori->is($kategori));
        $this->assertTrue($kategori->produk->contains($produk));
        $this->assertTrue($transaksi->pelanggan->is($pelanggan));
        $this->assertTrue($pelanggan->transaksi->contains($transaksi));
        $this->assertTrue($transaksi->detail->contains($detail));
        $this->assertTrue($detail->transaksi->is($transaksi));
        $this->assertTrue($detail->produk->is($produk));
        $this->assertTrue($produk->detailTransaksi->contains($detail));
        $this->assertSame(20000.0, $detail->subtotal);
    }
}

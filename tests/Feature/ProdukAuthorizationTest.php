<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_view_and_create_produk(): void
    {
        $kasir = User::factory()->kasir()->create();
        $kategori = Kategori::factory()->create();

        $this->actingAs($kasir)
            ->get(route('produk.index'))
            ->assertOk();

        $this->actingAs($kasir)
            ->get(route('produk.create'))
            ->assertOk();

        $response = $this->actingAs($kasir)
            ->post(route('produk.store'), [
                'id_kategori' => $kategori->id_kategori,
                'kode_produk' => 'SKU-TEST-01',
                'nama_produk' => 'Kopi Arabika',
                'harga' => 35000,
                'stok' => 15,
                'deskripsi' => 'Kopi pilihan',
                'is_featured' => true,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('tbl_produk', [
            'kode_produk' => 'SKU-TEST-01',
            'nama_produk' => 'Kopi Arabika',
            'harga' => 35000.00,
            'stok' => 15,
        ]);
    }

    public function test_kasir_gets_403_when_editing_updating_or_deleting_produk(): void
    {
        $kasir = User::factory()->kasir()->create();
        $produk = Produk::factory()->create();

        $this->actingAs($kasir)
            ->get(route('produk.edit', $produk))
            ->assertForbidden();

        $this->actingAs($kasir)
            ->put(route('produk.update', $produk), [
                'nama_produk' => 'Diubah Kasir',
                'harga' => 1000,
                'stok' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($kasir)
            ->delete(route('produk.destroy', $produk))
            ->assertForbidden();

        $this->assertDatabaseHas('tbl_produk', [
            'id_produk' => $produk->id_produk,
            'nama_produk' => $produk->nama_produk,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_create_update_and_delete_produk(): void
    {
        $admin = User::factory()->admin()->create();
        $kategori = Kategori::factory()->create();

        $this->actingAs($admin)
            ->post(route('produk.store'), [
                'id_kategori' => $kategori->id_kategori,
                'kode_produk' => 'ADM-001',
                'nama_produk' => 'Teh Hijau',
                'harga' => 12000,
                'stok' => 25,
            ])
            ->assertRedirect(route('produk.index'));

        $produk = Produk::query()->where('kode_produk', 'ADM-001')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('produk.edit', $produk))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('produk.update', $produk), [
                'id_kategori' => $kategori->id_kategori,
                'kode_produk' => 'ADM-001',
                'nama_produk' => 'Teh Hijau Premium',
                'harga' => 18000,
                'stok' => 30,
            ])
            ->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('tbl_produk', [
            'id_produk' => $produk->id_produk,
            'nama_produk' => 'Teh Hijau Premium',
            'harga' => 18000.00,
        ]);

        $this->actingAs($admin)
            ->delete(route('produk.destroy', $produk))
            ->assertRedirect(route('produk.index'));

        $this->assertSoftDeleted('tbl_produk', [
            'id_produk' => $produk->id_produk,
        ]);
    }

    public function test_produk_validation_rejects_negative_price_and_stock(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk Invalid',
                'harga' => -5000,
                'stok' => -2,
            ])
            ->assertSessionHasErrors(['harga', 'stok']);
    }

    public function test_kasir_and_admin_can_store_produk_with_barcode_preserving_leading_zeros(): void
    {
        $kasir = User::factory()->kasir()->create();
        $barcodeWithLeadingZero = '0123456789012';

        $response = $this->actingAs($kasir)->post(route('produk.store'), [
            'nama_produk' => 'Biskuit Cokelat',
            'kode_produk' => 'BSK-001',
            'barcode' => $barcodeWithLeadingZero,
            'harga' => 15000,
            'stok' => 50,
        ]);

        $response->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('tbl_produk', [
            'nama_produk' => 'Biskuit Cokelat',
            'barcode' => $barcodeWithLeadingZero,
        ]);

        $saved = Produk::query()->where('barcode', $barcodeWithLeadingZero)->first();
        $this->assertNotNull($saved);
        $this->assertSame($barcodeWithLeadingZero, $saved->barcode);
    }

    public function test_barcode_must_be_unique_across_products(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = Produk::factory()->create([
            'barcode' => '8991234567890',
        ]);

        $response = $this->actingAs($admin)->post(route('produk.store'), [
            'nama_produk' => 'Produk Duplikat Barcode',
            'barcode' => '8991234567890',
            'harga' => 20000,
            'stok' => 10,
        ]);

        $response->assertSessionHasErrors(['barcode']);
    }

    public function test_nullable_barcode_allows_multiple_products_without_barcode(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('produk.store'), [
            'nama_produk' => 'Produk Tanpa Barcode 1',
            'barcode' => '',
            'harga' => 10000,
            'stok' => 5,
        ])->assertRedirect(route('produk.index'));

        $this->actingAs($admin)->post(route('produk.store'), [
            'nama_produk' => 'Produk Tanpa Barcode 2',
            'barcode' => null,
            'harga' => 12000,
            'stok' => 8,
        ])->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('tbl_produk', [
            'nama_produk' => 'Produk Tanpa Barcode 1',
            'barcode' => null,
        ]);

        $this->assertDatabaseHas('tbl_produk', [
            'nama_produk' => 'Produk Tanpa Barcode 2',
            'barcode' => null,
        ]);
    }

    public function test_admin_can_update_barcode_and_kasir_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $kasir = User::factory()->kasir()->create();
        $produk = Produk::factory()->create([
            'barcode' => '8990000000001',
        ]);

        // Kasir update barcode -> 403 Forbidden
        $this->actingAs($kasir)->put(route('produk.update', $produk), [
            'nama_produk' => $produk->nama_produk,
            'barcode' => '8990000000002',
            'harga' => $produk->harga,
            'stok' => $produk->stok,
        ])->assertForbidden();

        // Admin update barcode -> Berhasil
        $this->actingAs($admin)->put(route('produk.update', $produk), [
            'nama_produk' => $produk->nama_produk,
            'barcode' => '8990000000002',
            'harga' => $produk->harga,
            'stok' => $produk->stok,
        ])->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('tbl_produk', [
            'id_produk' => $produk->id_produk,
            'barcode' => '8990000000002',
        ]);
    }

    public function test_produk_search_finds_item_by_barcode(): void
    {
        $admin = User::factory()->admin()->create();
        $targetProduk = Produk::factory()->create([
            'nama_produk' => 'Sabun Mandi Wangi',
            'barcode' => '8997778889990',
        ]);
        Produk::factory()->create([
            'nama_produk' => 'Sampo Rambut',
            'barcode' => '8991112223334',
        ]);

        $results = Produk::query()->search('8997778889990')->get();
        $this->assertCount(1, $results);
        $this->assertSame($targetProduk->id_produk, $results->first()->id_produk);

        $this->actingAs($admin)
            ->get(route('produk.index', ['search' => '8997778889990']))
            ->assertOk();
    }
}

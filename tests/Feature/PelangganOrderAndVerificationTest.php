<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PelangganOrderAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_order_and_upload_payment_proof_and_kasir_can_approve(): void
    {
        Storage::fake('public');

        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Sari Pembeli']);
        $customerUser = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'is_active' => true,
        ]);
        $kasir = User::factory()->kasir()->create(['name' => 'Kasir Verifikator']);
        $produk = Produk::factory()->create(['harga' => 50000, 'stok' => 10, 'is_active' => true]);

        // Halaman belanja dapat dibuka oleh pelanggan
        $this->actingAs($customerUser)
            ->get(route('pelanggan.order.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Pelanggan/Order'));

        // Kasir dan Admin tidak boleh mengakses endpoint checkout pelanggan
        $this->actingAs($kasir)
            ->get(route('pelanggan.order.create'))
            ->assertForbidden();

        // Pelanggan mengirim pesanan + upload bukti pembayaran
        $proofFile = UploadedFile::fake()->image('bukti-qris.jpg', 800, 800);

        $response = $this->actingAs($customerUser)->post(route('pelanggan.order.store'), [
            'metode_pembayaran' => 'qris',
            'bukti_pembayaran' => $proofFile,
            'catatan' => 'Sudah bayar via QRIS',
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 2],
            ],
        ]);

        $response->assertRedirect(route('pelanggan.dashboard'));

        $transaksi = Transaksi::query()->latest('id_transaksi')->firstOrFail();
        $this->assertSame(Transaksi::STATUS_MENUNGGU_VERIFIKASI, $transaksi->status);
        $this->assertSame('100000.00', $transaksi->subtotal);
        $this->assertSame('11000.00', $transaksi->pajak);
        $this->assertSame('111000.00', $transaksi->total_belanja);
        $this->assertNotNull($transaksi->bukti_pembayaran);
        Storage::disk('public')->assertExists($transaksi->bukti_pembayaran);

        // Stok sementara berkurang 2 (menjadi 8)
        $this->assertSame(8, $produk->fresh()->stok);

        // Pelanggan tidak boleh memverifikasi pembayarannya sendiri
        $this->actingAs($customerUser)
            ->patch(route('transaksi.verify', $transaksi), ['decision' => 'approve'])
            ->assertForbidden();

        // Kasir menyetujui pembayaran
        $this->actingAs($kasir)
            ->patch(route('transaksi.verify', $transaksi), [
                'decision' => 'approve',
                'catatan_verifikasi' => 'Dana QRIS sudah diterima',
            ])
            ->assertRedirect();

        $transaksi->refresh();
        $this->assertSame(Transaksi::STATUS_SELESAI, $transaksi->status);
        $this->assertSame($kasir->id, $transaksi->verified_by);
        $this->assertNotNull($transaksi->verified_at);
        $this->assertSame('Dana QRIS sudah diterima', $transaksi->catatan_verifikasi);
        $this->assertSame($kasir->name, $transaksi->nama_kasir);
    }

    public function test_admin_can_reject_payment_proof_and_restore_product_stock(): void
    {
        Storage::fake('public');

        $pelanggan = Pelanggan::factory()->create();
        $customerUser = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'is_active' => true,
        ]);
        $admin = User::factory()->admin()->create(['name' => 'Admin Verifikator']);
        $produk = Produk::factory()->create(['harga' => 25000, 'stok' => 5, 'is_active' => true]);

        $this->actingAs($customerUser)->post(route('pelanggan.order.store'), [
            'metode_pembayaran' => 'transfer',
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti-transfer.png', 600, 600),
            'items' => [
                ['id_produk' => $produk->id_produk, 'jumlah' => 3],
            ],
        ])->assertRedirect();

        $transaksi = Transaksi::query()->latest('id_transaksi')->firstOrFail();
        $this->assertSame(2, $produk->fresh()->stok);

        // Admin menolak bukti pembayaran -> status dibatalkan & stok kembali ke 5
        $this->actingAs($admin)
            ->patch(route('transaksi.verify', $transaksi), [
                'decision' => 'reject',
                'catatan_verifikasi' => 'Nominal transfer tidak sesuai',
            ])
            ->assertRedirect();

        $transaksi->refresh();
        $this->assertSame(Transaksi::STATUS_DIBATALKAN, $transaksi->status);
        $this->assertSame($admin->id, $transaksi->verified_by);
        $this->assertSame('Nominal transfer tidak sesuai', $transaksi->catatan_verifikasi);
        $this->assertSame(5, $produk->fresh()->stok);
    }
}

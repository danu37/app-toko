<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Prompt11SecurityAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validation_rate_limit_and_logout_invalidate_session(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin.audit@toko.test',
        ]);

        // 1. Validasi field kosong & format email tidak valid
        $this->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->post('/login', [
            'email' => 'bukan-email',
            'password' => 'secret',
        ])->assertSessionHasErrors(['email']);

        // 2. Percobaan gagal berulang memicu rate limiting pada percobaan ke-6
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $admin->email,
                'password' => 'salah-terus',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        // 3. Login Kasir valid, lalu logout memproteksi ulang halaman internal
        $kasir = User::factory()->kasir()->create([
            'email' => 'kasir.audit@toko.test',
        ]);

        $this->post('/login', [
            'email' => $kasir->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($kasir);
        $this->get(route('dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('transaksi.create'))->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_any_protected_get_or_mutation_endpoint(): void
    {
        $cabang = Cabang::factory()->create();
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);
        $pelanggan = Pelanggan::factory()->create();
        $user = User::factory()->kasir()->create();
        $transaksi = Transaksi::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $user->id,
        ]);

        $getRoutes = [
            route('dashboard'),
            route('produk.index'),
            route('produk.create'),
            route('produk.show', $produk),
            route('produk.edit', $produk),
            route('pelanggan.index'),
            route('pelanggan.create'),
            route('pelanggan.show', $pelanggan),
            route('pelanggan.edit', $pelanggan),
            route('transaksi.index'),
            route('transaksi.riwayat'),
            route('transaksi.create'),
            route('transaksi.show', $transaksi),
            route('transaksi.nota', ['transaksi' => $transaksi->id_transaksi, 'size' => '58mm']),
            route('transaksi.nota', ['transaksi' => $transaksi->id_transaksi, 'size' => '80mm']),
            route('kategori.index'),
            route('cabang.index'),
            route('cabang.create'),
            route('cabang.edit', $cabang),
            route('laporan.index'),
            route('laporan.transaksi'),
            route('laporan.produk'),
            route('laporan.pelanggan'),
            route('laporan.export', ['report' => 'sales']),
            route('pengguna.index'),
            route('pengguna.create'),
            route('pengguna.edit', $user),
            route('pengaturan.index'),
            route('profile.edit'),
        ];

        foreach ($getRoutes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->post(route('produk.store'), [])->assertRedirect(route('login'));
        $this->put(route('produk.update', $produk), [])->assertRedirect(route('login'));
        $this->delete(route('produk.destroy', $produk))->assertRedirect(route('login'));
        $this->post(route('pelanggan.store'), [])->assertRedirect(route('login'));
        $this->put(route('pelanggan.update', $pelanggan), [])->assertRedirect(route('login'));
        $this->delete(route('pelanggan.destroy', $pelanggan))->assertRedirect(route('login'));
        $this->post(route('transaksi.store'), [])->assertRedirect(route('login'));
        $this->delete(route('transaksi.destroy', $transaksi))->assertRedirect(route('login'));
        $this->post(route('kategori.store'), [])->assertRedirect(route('login'));
        $this->post(route('cabang.store'), [])->assertRedirect(route('login'));
        $this->post(route('pengguna.store'), [])->assertRedirect(route('login'));
        $this->patch(route('pengguna.role', $user), [])->assertRedirect(route('login'));
        $this->patch(route('profile.update'), [])->assertRedirect(route('login'));
        $this->delete(route('profile.destroy'), [])->assertRedirect(route('login'));
    }

    public function test_inactive_user_and_downgraded_role_are_blocked_across_profile_and_admin_endpoints(): void
    {
        $user = User::factory()->admin()->create();

        // Admin awalnya diizinkan membuka pengaturan & laporan
        $this->actingAs($user)->get(route('pengaturan.index'))->assertOk();

        // Simulasi admin diturunkan menjadi kasir di database oleh admin lain
        $user->update(['role' => User::ROLE_KASIR]);

        $this->actingAs($user->fresh())->get(route('pengaturan.index'))->assertForbidden();
        $this->actingAs($user->fresh())->get(route('laporan.index'))->assertForbidden();
        $this->actingAs($user->fresh())->get(route('pengguna.index'))->assertForbidden();

        // Simulasi akun dinonaktifkan saat sesi masih aktif -> wajib 403 di seluruh endpoint termasuk profile & password
        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user->fresh())->get(route('profile.edit'))->assertForbidden();
        $this->actingAs($user->fresh())->patch(route('profile.update'), [
            'name' => 'Blocked',
            'email' => $user->email,
        ])->assertForbidden();
        $this->actingAs($user->fresh())->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertForbidden();
        $this->actingAs($user->fresh())->delete(route('profile.destroy'), [
            'password' => 'password',
        ])->assertForbidden();

        // Password tidak boleh berubah saat ditolak
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        // Akun nonaktif tetap diizinkan logout cleanly
        $this->actingAs($user->fresh())->post(route('logout'))->assertRedirect('/');
    }

    public function test_transaction_rejects_duplicates_soft_deleted_products_and_underpaid_non_cash_atomically(): void
    {
        $cabang = Cabang::factory()->create();
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create();

        $p1 = Produk::factory()->create(['nama_produk' => 'Kopi Bubuk', 'harga' => 25000, 'stok' => 10]);
        $p2 = Produk::factory()->create(['nama_produk' => 'Gula Aren', 'harga' => 18000, 'stok' => 10]);

        // 1. Produk duplikat pada keranjang wajib ditolak tanpa mengurangi stok
        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 100000,
                'items' => [
                    ['id_produk' => $p1->id_produk, 'jumlah' => 1],
                    ['id_produk' => $p1->id_produk, 'jumlah' => 2],
                ],
            ])
            ->assertSessionHasErrors(['items.0.id_produk', 'items.1.id_produk']);

        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertSame(10, $p1->fresh()->stok);

        // 2. Non-tunai namun uang_bayar dikirim lebih kecil dari total harus ditolak (anti-tampering)
        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'qris',
                'uang_bayar' => 1000,
                'items' => [
                    ['id_produk' => $p1->id_produk, 'jumlah' => 1],
                    ['id_produk' => $p2->id_produk, 'jumlah' => 1],
                ],
            ])
            ->assertSessionHasErrors('uang_bayar');

        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertSame(10, $p1->fresh()->stok);
        $this->assertSame(10, $p2->fresh()->stok);

        // 3. Jika admin soft-delete produk lalu kasir mencoba checkout item tsb bersama item lain, seluruh transaksi ditolak
        $this->actingAs($admin)->delete(route('produk.destroy', $p2))->assertRedirect(route('produk.index'));

        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 100000,
                'items' => [
                    ['id_produk' => $p1->id_produk, 'jumlah' => 2],
                    ['id_produk' => $p2->id_produk, 'jumlah' => 1],
                ],
            ])
            ->assertSessionHasErrors('items.1.id_produk');

        $this->assertDatabaseCount('tbl_transaksi', 0);
        $this->assertSame(10, $p1->fresh()->stok);
    }

    public function test_receipt_and_transaction_detail_remain_intact_after_product_soft_delete_across_58mm_and_80mm(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Audit', 'kota' => 'Surabaya']);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $kasir = User::factory()->kasir()->create(['name' => 'Kasir Struk Panjang Banget Namanya', 'id_cabang' => $cabang->id_cabang]);
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Pelanggan VIP Panjang Sekali Namanya']);

        $produk = Produk::factory()->create([
            'kode_produk' => 'AUD-001',
            'nama_produk' => 'Paket Sembako Nusantara Edisi Keluarga Besar',
            'harga' => 125500,
            'stok' => 8,
        ]);

        // Kasir memproses transaksi dengan percobaan spoof user_id/nama_kasir/status/kode_transaksi dari payload
        $this->actingAs($kasir)
            ->post(route('transaksi.store'), [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'user_id' => $admin->id,
                'nama_kasir' => 'Kasir Palsu Spoof',
                'kode_transaksi' => 'FAKE-TRX-999',
                'status' => Transaksi::STATUS_DIBATALKAN,
                'metode_pembayaran' => 'tunai',
                'uang_bayar' => 300000,
                'items' => [
                    ['id_produk' => $produk->id_produk, 'jumlah' => 2, 'harga' => 100],
                ],
            ])
            ->assertRedirect();

        $trx = Transaksi::query()->latest('id_transaksi')->firstOrFail();

        $this->assertSame($kasir->id, $trx->user_id);
        $this->assertSame('Kasir Struk Panjang Banget Namanya', $trx->nama_kasir);
        $this->assertNotSame('FAKE-TRX-999', $trx->kode_transaksi);
        $this->assertSame(Transaksi::STATUS_SELESAI, $trx->status);
        $this->assertSame('251000.00', $trx->total_belanja);
        $this->assertSame('49000.00', $trx->kembalian);
        $this->assertSame(6, $produk->fresh()->stok);

        // Admin menghapus produk (soft delete) setelah transaksi tercatat
        $this->actingAs($admin)->delete(route('produk.destroy', $produk))->assertRedirect();

        // Detail & Cetak Nota 58mm / 80mm tetap tampil lengkap tanpa null error dan memuat snapshot harga/nama/SKU
        foreach (['58mm', '80mm'] as $size) {
            $this->actingAs($kasir)
                ->get(route('transaksi.nota', ['transaksi' => $trx->id_transaksi, 'size' => $size]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Transaksi/Receipt')
                    ->where('initialSize', $size)
                    ->where('transaction.kode_transaksi', $trx->kode_transaksi)
                    ->where('transaction.total_belanja', 251000)
                    ->where('transaction.uang_bayar', 300000)
                    ->where('transaction.kembalian', 49000)
                    ->where('transaction.nama_kasir', 'Kasir Struk Panjang Banget Namanya')
                    ->where('transaction.kasir.name', 'Kasir Struk Panjang Banget Namanya')
                    ->where('transaction.pelanggan.nama_pelanggan', 'Pelanggan VIP Panjang Sekali Namanya')
                    ->where('transaction.detail.0.nama_produk', 'Paket Sembako Nusantara Edisi Keluarga Besar')
                    ->where('transaction.detail.0.subtotal', 251000)
                    ->where('transaction.detail.0.produk.kode_produk', 'AUD-001')
                );
        }

        $this->actingAs($kasir)
            ->get(route('transaksi.show', $trx))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transaksi/Show')
                ->where('transaction.detail.0.produk.kode_produk', 'AUD-001')
            );
    }

    public function test_uploaded_product_image_is_optimized_and_inertia_renders_error_pages(): void
    {
        Storage::fake('public');

        $kasir = User::factory()->kasir()->create();
        $image = UploadedFile::fake()->image('oversized-product.jpg', 2400, 1800);

        $this->actingAs($kasir)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk Gambar Optimasi',
                'harga' => 45000,
                'stok' => 12,
                'gambar' => $image,
            ])
            ->assertRedirect(route('produk.index'));

        $produk = Produk::query()->where('nama_produk', 'Produk Gambar Optimasi')->firstOrFail();

        $this->assertNotNull($produk->gambar);
        $this->assertStringEndsWith('.jpg', $produk->gambar);
        Storage::disk('public')->assertExists($produk->gambar);

        $binary = Storage::disk('public')->get($produk->gambar);
        $info = getimagesizefromstring($binary);
        $this->assertIsArray($info);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, strlen($binary));

        // Halaman 403 & 404 mengembalikan status HTTP otoritatif dengan komponen Error Inertia
        $this->actingAs($kasir)
            ->get(route('pengguna.index'))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 403)
            );

        $this->get('/shop/produk-yang-tidak-pernah-ada')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 404)
            );
    }
}

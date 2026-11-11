<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOnlyModulesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_cannot_access_admin_only_endpoints(): void
    {
        $kasir = User::factory()->kasir()->create();
        $kategori = Kategori::factory()->create();
        $cabang = Cabang::factory()->create();
        $otherUser = User::factory()->kasir()->create();

        // Kategori mutations
        $this->actingAs($kasir)->post(route('kategori.store'), ['nama_kategori' => 'Baru'])->assertForbidden();
        $this->actingAs($kasir)->put(route('kategori.update', $kategori), ['nama_kategori' => 'Ubah'])->assertForbidden();
        $this->actingAs($kasir)->delete(route('kategori.destroy', $kategori))->assertForbidden();

        // Cabang mutations
        $this->actingAs($kasir)->get(route('cabang.create'))->assertForbidden();
        $this->actingAs($kasir)->post(route('cabang.store'), ['nama_cabang' => 'Baru', 'kota' => 'Solo'])->assertForbidden();
        $this->actingAs($kasir)->get(route('cabang.edit', $cabang))->assertForbidden();
        $this->actingAs($kasir)->put(route('cabang.update', $cabang), ['nama_cabang' => 'Ubah', 'kota' => 'Solo'])->assertForbidden();
        $this->actingAs($kasir)->delete(route('cabang.destroy', $cabang))->assertForbidden();

        // Laporan
        $this->actingAs($kasir)->get(route('laporan.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.transaksi'))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.produk'))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.pelanggan'))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.export', ['report' => 'sales']))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.export', ['report' => 'transactions']))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.export', ['report' => 'products']))->assertForbidden();
        $this->actingAs($kasir)->get(route('laporan.export', ['report' => 'customers']))->assertForbidden();

        // Pengguna
        $this->actingAs($kasir)->get(route('pengguna.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('pengguna.create'))->assertForbidden();
        $this->actingAs($kasir)->post(route('pengguna.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@toko.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertForbidden();
        $this->actingAs($kasir)->delete(route('pengguna.destroy', $otherUser))->assertForbidden();
    }

    public function test_admin_can_manage_users_categories_branches_and_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $cabang = Cabang::factory()->create();

        $this->actingAs($admin)->get(route('laporan.index'))->assertOk();
        $this->actingAs($admin)->get(route('laporan.transaksi'))->assertOk();
        $this->actingAs($admin)->get(route('laporan.produk'))->assertOk();
        $this->actingAs($admin)->get(route('laporan.pelanggan'))->assertOk();
        $this->actingAs($admin)->get(route('pengguna.index'))->assertOk();

        $this->actingAs($admin)
            ->post(route('pengguna.store'), [
                'name' => 'Kasir Baru',
                'email' => 'kasir.baru@toko.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'kasir',
                'id_cabang' => $cabang->id_cabang,
                'is_active' => true,
            ])
            ->assertRedirect(route('pengguna.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'kasir.baru@toko.test',
            'role' => 'kasir',
            'id_cabang' => $cabang->id_cabang,
        ]);

        // Admin tidak boleh menghapus dirinya sendiri
        $this->actingAs($admin)
            ->delete(route('pengguna.destroy', $admin))
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_login_or_access_dashboard(): void
    {
        $inactive = User::factory()->kasir()->inactive()->create();

        $this->post('/login', [
            'email' => $inactive->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($inactive)
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}

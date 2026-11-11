<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAuthenticationAndInertiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_on_internal_routes(): void
    {
        $routes = [
            route('dashboard'),
            route('produk.index'),
            route('produk.create'),
            route('pelanggan.index'),
            route('transaksi.index'),
            route('transaksi.create'),
            route('kategori.index'),
            route('cabang.index'),
            route('laporan.index'),
            route('pengguna.index'),
            route('pengaturan.index'),
            route('profile.edit'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_login_redirects_and_shares_full_inertia_permissions(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Pusat']);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('auth.user.id', $admin->id)
                ->where('auth.user.role', 'admin')
                ->where('auth.can.isAdmin', true)
                ->where('auth.can.isKasir', false)
                ->where('auth.can.createProducts', true)
                ->where('auth.can.editProducts', true)
                ->where('auth.can.deleteProducts', true)
                ->where('auth.can.editCustomers', true)
                ->where('auth.can.deleteCustomers', true)
                ->where('auth.can.viewReports', true)
                ->where('auth.can.manageUsers', true)
                ->where('auth.can.manageSettings', true)
                ->has('summary.total_omzet')
                ->has('summary.total_transaksi')
            );
    }

    public function test_kasir_login_redirects_and_shares_restricted_inertia_permissions(): void
    {
        $cabang = Cabang::factory()->create(['nama_cabang' => 'Cabang Barat']);
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);

        $response = $this->post('/login', [
            'email' => $kasir->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($kasir);
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($kasir)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('auth.user.id', $kasir->id)
                ->where('auth.user.role', 'kasir')
                ->where('auth.can.isAdmin', false)
                ->where('auth.can.isKasir', true)
                ->where('auth.can.viewProducts', true)
                ->where('auth.can.createProducts', true)
                ->where('auth.can.editProducts', false)
                ->where('auth.can.deleteProducts', false)
                ->where('auth.can.viewCustomers', true)
                ->where('auth.can.createCustomers', true)
                ->where('auth.can.editCustomers', false)
                ->where('auth.can.deleteCustomers', false)
                ->where('auth.can.viewTransactions', true)
                ->where('auth.can.viewTransactionHistory', true)
                ->where('auth.can.viewDailySales', true)
                ->where('auth.can.createTransactions', true)
                ->where('auth.can.viewReports', false)
                ->where('auth.can.manageUsers', false)
                ->where('auth.can.manageSettings', false)
                ->has('summary.transaksi_hari_ini')
                ->has('summary.pelanggan_dilayani_hari_ini')
                ->has('summary.omzet_hari_ini')
                ->missing('summary.total_omzet')
                ->missing('salesChart')
                ->missing('topProducts')
                ->missing('stockSummary')
            );
    }

    public function test_kasir_gets_403_on_pengaturan_and_pengguna_edit_and_update(): void
    {
        $kasir = User::factory()->kasir()->create();
        $otherUser = User::factory()->kasir()->create();

        $this->actingAs($kasir)->get(route('pengaturan.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('pengguna.edit', $otherUser))->assertForbidden();
        $this->actingAs($kasir)->put(route('pengguna.update', $otherUser), [
            'name' => 'Hack',
            'email' => $otherUser->email,
            'role' => 'admin',
        ])->assertForbidden();
    }

    public function test_kasir_cannot_escalate_role_via_profile_update(): void
    {
        $kasir = User::factory()->kasir()->create();

        $this->actingAs($kasir)
            ->patch(route('profile.update'), [
                'name' => 'Kasir Tetap',
                'email' => $kasir->email,
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertRedirect(route('profile.edit'));

        $kasir->refresh();
        $this->assertSame(User::ROLE_KASIR, $kasir->role);
        $this->assertTrue($kasir->is_active);
    }

    public function test_unknown_role_user_gets_403_on_operational_and_admin_routes(): void
    {
        $unknown = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($unknown)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($unknown)->get(route('produk.index'))->assertForbidden();
        $this->actingAs($unknown)->get(route('pengaturan.index'))->assertForbidden();
    }

    public function test_admin_can_access_pengaturan_and_cannot_demote_or_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('pengaturan.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pengaturan/Index')
                ->where('settings.timezone', 'Asia/Jakarta')
            );

        $this->actingAs($admin)
            ->put(route('pengguna.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'kasir',
                'is_active' => false,
            ])
            ->assertSessionHasErrors(['role', 'is_active']);

        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_all_inertia_page_routes_render_expected_components_for_admin_and_kasir(): void
    {
        $cabang = Cabang::factory()->create();
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori, 'is_active' => true]);
        $pelanggan = Pelanggan::factory()->create();
        $kasir = User::factory()->kasir()->create(['id_cabang' => $cabang->id_cabang]);
        $admin = User::factory()->admin()->create(['id_cabang' => $cabang->id_cabang]);
        $transaksi = Transaksi::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_cabang' => $cabang->id_cabang,
            'user_id' => $kasir->id,
        ]);

        // Storefront
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Store/Index'));
        $this->get(route('store.shop'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Store/Shop'));
        $this->get(route('store.product', $produk))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Store/ProductDetail'));

        // Kasir accessible pages
        $this->actingAs($kasir)->get(route('produk.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Produk/Index'));
        $this->actingAs($kasir)->get(route('produk.create'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Produk/Create'));
        $this->actingAs($kasir)->get(route('produk.show', $produk))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Produk/Show'));
        $this->actingAs($kasir)->get(route('pelanggan.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pelanggan/Index'));
        $this->actingAs($kasir)->get(route('pelanggan.create'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pelanggan/Create'));
        $this->actingAs($kasir)->get(route('pelanggan.show', $pelanggan))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pelanggan/Show'));
        $this->actingAs($kasir)->get(route('transaksi.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Transaksi/Index'));
        $this->actingAs($kasir)->get(route('transaksi.riwayat'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Transaksi/Riwayat'));
        $this->actingAs($kasir)->get(route('transaksi.create'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Transaksi/Create'));
        $this->actingAs($kasir)->get(route('transaksi.show', $transaksi))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Transaksi/Show'));
        $this->actingAs($kasir)->get(route('kategori.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Kategori/Index'));
        $this->actingAs($kasir)->get(route('cabang.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Cabang/Index'));
        $this->actingAs($kasir)->get(route('profile.edit'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Profile/Edit'));

        // Admin accessible pages
        $this->actingAs($admin)->get(route('produk.edit', $produk))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Produk/Edit'));
        $this->actingAs($admin)->get(route('pelanggan.edit', $pelanggan))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pelanggan/Edit'));
        $this->actingAs($admin)->get(route('cabang.create'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Cabang/Create'));
        $this->actingAs($admin)->get(route('cabang.edit', $cabang))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Cabang/Edit'));
        $this->actingAs($admin)->get(route('laporan.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Laporan/Index'));
        $this->actingAs($admin)->get(route('laporan.transaksi'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Laporan/Transaksi'));
        $this->actingAs($admin)->get(route('laporan.pelanggan'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Laporan/Pelanggan'));
        $this->actingAs($admin)->get(route('pengguna.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pengguna/Index'));
        $this->actingAs($admin)->get(route('pengguna.create'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pengguna/Create'));
        $this->actingAs($admin)->get(route('pengguna.edit', $kasir))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pengguna/Edit'));
        $this->actingAs($admin)->get(route('pengaturan.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Pengaturan/Index'));
    }
}

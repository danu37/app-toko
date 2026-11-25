<?php

namespace Tests\Feature\Auth;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    }

    public function test_customer_can_register_and_role_is_always_pelanggan(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Pelanggan',
            'email' => 'budi@pelanggan.test',
            'no_hp' => '081234567890',
            'kota' => 'Ponorogo',
            'alamat' => 'Jl. Merdeka No. 1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // Upaya memanipulasi role dari client harus diabaikan backend
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('pelanggan.dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'budi@pelanggan.test')->firstOrFail();
        $this->assertSame(User::ROLE_PELANGGAN, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->id_pelanggan);

        $pelanggan = Pelanggan::query()->findOrFail($user->id_pelanggan);
        $this->assertSame('Budi Pelanggan', $pelanggan->nama_pelanggan);
        $this->assertSame('081234567890', $pelanggan->no_hp);
        $this->assertSame('Ponorogo', $pelanggan->kota);
    }

    public function test_registration_rejects_duplicate_email_or_mismatched_password(): void
    {
        User::factory()->create(['email' => 'sudahada@test.com']);

        $this->post('/register', [
            'name' => 'Duplikat',
            'email' => 'sudahada@test.com',
            'password' => 'password123',
            'password_confirmation' => 'beda123',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }
}

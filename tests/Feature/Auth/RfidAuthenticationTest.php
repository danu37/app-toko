<?php

namespace Tests\Feature\Auth;

use App\Models\Pelanggan;
use App\Models\RfidCard;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Admin login menggunakan RFID valid.
     */
    public function test_admin_can_authenticate_using_valid_rfid_card(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $admin->id,
            'uid' => '04A1B2C3D4',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->post('/login/rfid', [
            'rfid_uid' => '04A1B2C3D4',
            'remember' => true,
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('success');
    }

    /**
     * 2. Kasir login menggunakan RFID valid.
     */
    public function test_cashier_can_authenticate_using_valid_rfid_card(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $cashier->id,
            'uid' => 'KASIR_CARD_123',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->post('/login/rfid', [
            'rfid_uid' => 'KASIR_CARD_123',
        ]);

        $this->assertAuthenticatedAs($cashier);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * 3. Pelanggan login menggunakan RFID valid.
     */
    public function test_customer_can_authenticate_using_valid_rfid_card(): void
    {
        $pelangganMaster = Pelanggan::factory()->create([
            'nama_pelanggan' => 'Siti Pelanggan',
        ]);

        $customerUser = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'id_pelanggan' => $pelangganMaster->id_pelanggan,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $customerUser->id,
            'uid' => 'PELANGGAN_CARD_999',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->post('/login/rfid', [
            'rfid_uid' => 'PELANGGAN_CARD_999',
        ]);

        $this->assertAuthenticatedAs($customerUser);
        $response->assertRedirect(route('pelanggan.dashboard', absolute: false));
    }

    /**
     * 4. Login menggunakan UID yang tidak terdaftar.
     */
    public function test_guest_cannot_authenticate_with_unregistered_rfid_uid(): void
    {
        $response = $this->from('/login')->post('/login/rfid', [
            'rfid_uid' => 'UNKNOWN_UID_999',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['rfid_uid' => 'Kartu RFID tidak dikenali.']);
    }

    /**
     * 5. Login menggunakan kartu yang dinonaktifkan.
     */
    public function test_login_fails_when_rfid_card_is_disabled(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $admin->id,
            'uid' => 'CARD_DISABLED_01',
            'status' => RfidCard::STATUS_NONAKTIF,
        ]);

        $response = $this->from('/login')->post('/login/rfid', [
            'rfid_uid' => 'CARD_DISABLED_01',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['rfid_uid' => 'Kartu RFID ini sedang dinonaktifkan. Hubungi administrator.']);
    }

    /**
     * 6. Login menggunakan akun yang dinonaktifkan.
     */
    public function test_login_fails_when_user_account_is_disabled(): void
    {
        $inactiveUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
        ]);

        RfidCard::create([
            'user_id' => $inactiveUser->id,
            'uid' => 'ACTIVE_CARD_ON_INACTIVE_USER',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->from('/login')->post('/login/rfid', [
            'rfid_uid' => 'ACTIVE_CARD_ON_INACTIVE_USER',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['rfid_uid' => 'Akun pengguna sedang dinonaktifkan. Hubungi administrator.']);
    }

    /**
     * 7. UID duplikat ditolak.
     */
    public function test_duplicate_rfid_uid_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $admin->id,
            'uid' => 'EXISTING_CARD_123',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->actingAs($admin)->from('/pengguna/create')->post('/pengguna', [
            'name' => 'Duplikat Pengguna',
            'email' => 'duplikat@toko.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_PELANGGAN,
            'rfid_uid' => 'EXISTING_CARD_123',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('rfid_uid');
    }

    /**
     * 8. Admin tidak dapat diakses oleh Kasir.
     */
    public function test_admin_endpoints_cannot_be_accessed_by_cashier(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->get('/pengguna');
        $response->assertStatus(403);
    }

    /**
     * 9. Admin tidak dapat diakses oleh Pelanggan.
     */
    public function test_admin_endpoints_cannot_be_accessed_by_customer(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer)->get('/pengguna');
        $response->assertStatus(403);
    }

    /**
     * 10. Kasir tidak dapat mengakses dashboard Pelanggan jika tidak diizinkan.
     */
    public function test_cashier_cannot_access_customer_dashboard(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->get('/pelanggan/dashboard');
        $response->assertStatus(403);
    }

    /**
     * 11. Pelanggan tidak dapat mengakses fitur Kasir (POS).
     */
    public function test_customer_cannot_access_cashier_pos_features(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer)->get('/transaksi/create');
        $response->assertStatus(403);

        $response = $this->actingAs($customer)->get('/dashboard');
        $response->assertStatus(403);
    }

    /**
     * 12. Login email dan password tetap berfungsi untuk semua role.
     */
    public function test_email_and_password_login_works_for_all_roles(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Login Admin
        $resp = $this->post('/login', ['email' => $admin->email, 'password' => 'password123']);
        $this->assertAuthenticatedAs($admin);
        $resp->assertRedirect(route('dashboard', absolute: false));
        $this->post('/logout');

        // Login Kasir
        $resp = $this->post('/login', ['email' => $cashier->email, 'password' => 'password123']);
        $this->assertAuthenticatedAs($cashier);
        $resp->assertRedirect(route('dashboard', absolute: false));
        $this->post('/logout');

        // Login Pelanggan
        $resp = $this->post('/login', ['email' => $customer->email, 'password' => 'password123']);
        $this->assertAuthenticatedAs($customer);
        $resp->assertRedirect(route('pelanggan.dashboard', absolute: false));
    }

    /**
     * 13. Penggantian dan penonaktifan kartu berjalan dengan benar.
     */
    public function test_card_replacement_and_deactivation_work_properly(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'is_active' => true,
        ]);

        $card = RfidCard::create([
            'user_id' => $target->id,
            'uid' => 'CARD_INITIAL_111',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        // Admin menonaktifkan kartu
        $resp = $this->actingAs($admin)->patch('/pengguna/'.$target->id.'/rfid/'.$card->id.'/toggle');
        $resp->assertRedirect();
        $this->assertEquals(RfidCard::STATUS_NONAKTIF, $card->fresh()->status);

        // Target tidak bisa login saat kartu nonaktif
        $this->post('/logout');
        $failResp = $this->post('/login/rfid', ['rfid_uid' => 'CARD_INITIAL_111']);
        $this->assertGuest();
        $failResp->assertSessionHasErrors('rfid_uid');

        // Admin mengaktifkan kembali kartu
        $this->actingAs($admin)->patch('/pengguna/'.$target->id.'/rfid/'.$card->id.'/toggle');
        $this->assertEquals(RfidCard::STATUS_AKTIF, $card->fresh()->status);

        // Admin mengganti UID kartu
        $this->actingAs($admin)->put('/pengguna/'.$target->id, [
            'name' => $target->name,
            'email' => $target->email,
            'role' => User::ROLE_PELANGGAN,
            'rfid_uid' => 'CARD_REPLACED_222',
            'is_active' => true,
        ]);

        $this->assertEquals('CARD_REPLACED_222', $target->fresh()->rfid_uid);
        $this->assertEquals('CARD_REPLACED_222', $card->fresh()->uid);

        // Login dengan kartu baru berhasil
        $this->post('/logout');
        $successResp = $this->post('/login/rfid', ['rfid_uid' => 'CARD_REPLACED_222']);
        $this->assertAuthenticatedAs($target);
        $successResp->assertRedirect(route('pelanggan.dashboard', absolute: false));
    }

    /**
     * Uji rate limiting pada login RFID.
     */
    public function test_rfid_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login/rfid', [
                'rfid_uid' => 'WRONG_CARD_'.$i,
            ]);
            $response->assertSessionHasErrors('rfid_uid');
        }

        // 6th attempt should trigger rate limiting lockout
        $response = $this->post('/login/rfid', [
            'rfid_uid' => 'WRONG_CARD_6',
        ]);

        $response->assertSessionHasErrors('rfid_uid');
        $error = session('errors')->first('rfid_uid');
        $this->assertStringContainsString('Terlalu banyak percobaan login RFID', $error);
    }

    /**
     * Uji dashboard pelanggan dan proteksi nota antar pelanggan.
     */
    public function test_customer_can_access_own_dashboard_and_view_own_receipt(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        $customerUser = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'is_active' => true,
        ]);

        $ownTransaction = Transaksi::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
        ]);

        $otherCustomer = Pelanggan::factory()->create();
        $otherTransaction = Transaksi::factory()->create([
            'id_pelanggan' => $otherCustomer->id_pelanggan,
        ]);

        // Customer can access own dashboard
        $response = $this->actingAs($customerUser)->get('/pelanggan/dashboard');
        $response->assertStatus(200);

        // Customer can view own receipt
        $response = $this->actingAs($customerUser)->get('/pelanggan/transaksi/'.$ownTransaction->id_transaksi.'/nota');
        $response->assertStatus(200);

        // Customer gets 403 trying to view another customer's receipt
        $response = $this->actingAs($customerUser)->get('/pelanggan/transaksi/'.$otherTransaction->id_transaksi.'/nota');
        $response->assertStatus(403);
    }

    /**
     * Uji tap RFID langsung dari halaman Home (Storefront) untuk Admin.
     */
    public function test_tapping_rfid_card_from_home_page_logs_in_admin_and_redirects_to_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $admin->id,
            'uid' => 'ADMIN_HOME_RFID_01',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->from(route('home'))->post(route('login.rfid'), [
            'rfid_uid' => 'ADMIN_HOME_RFID_01',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
    }

    /**
     * Uji tap RFID langsung dari halaman Home (Storefront) untuk Kasir.
     */
    public function test_tapping_rfid_card_from_home_page_logs_in_cashier_and_redirects_to_dashboard(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $cashier->id,
            'uid' => 'KASIR_HOME_RFID_01',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->from(route('home'))->post(route('login.rfid'), [
            'rfid_uid' => 'KASIR_HOME_RFID_01',
        ]);

        $this->assertAuthenticatedAs($cashier);
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
    }

    /**
     * Uji tap RFID langsung dari halaman Home (Storefront) untuk Pelanggan.
     */
    public function test_tapping_rfid_card_from_home_page_logs_in_customer_and_redirects_to_customer_dashboard(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        $customerUser = User::factory()->create([
            'role' => User::ROLE_PELANGGAN,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'is_active' => true,
        ]);

        RfidCard::create([
            'user_id' => $customerUser->id,
            'uid' => 'PELANGGAN_HOME_RFID_01',
            'status' => RfidCard::STATUS_AKTIF,
        ]);

        $response = $this->from(route('home'))->post(route('login.rfid'), [
            'rfid_uid' => 'PELANGGAN_HOME_RFID_01',
        ]);

        $this->assertAuthenticatedAs($customerUser);
        $response->assertRedirect(route('pelanggan.dashboard'));
        $response->assertSessionHas('success');
    }

    /**
     * Uji tap RFID gagal langsung dari halaman Home mengembalikan error ke halaman Home.
     */
    public function test_tapping_invalid_rfid_card_from_home_page_fails_gracefully_back_to_home(): void
    {
        $response = $this->from(route('home'))->post(route('login.rfid'), [
            'rfid_uid' => 'CARD_NOT_FOUND_999',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors(['rfid_uid' => 'Kartu RFID tidak dikenali.']);
    }
}

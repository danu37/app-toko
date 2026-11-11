<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelangganAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_view_and_create_pelanggan(): void
    {
        $kasir = User::factory()->kasir()->create();

        $this->actingAs($kasir)
            ->get(route('pelanggan.index'))
            ->assertOk();

        $this->actingAs($kasir)
            ->post(route('pelanggan.store'), [
                'nama_pelanggan' => 'Siti Aminah',
                'kota' => 'Ponorogo',
                'no_hp' => '081234567890',
                'alamat' => 'Jl. Merdeka No. 1',
            ])
            ->assertRedirect(route('pelanggan.index'));

        $this->assertDatabaseHas('tbl_pelanggan', [
            'nama_pelanggan' => 'Siti Aminah',
            'kota' => 'Ponorogo',
            'no_hp' => '081234567890',
        ]);
    }

    public function test_kasir_gets_403_when_editing_updating_or_deleting_pelanggan(): void
    {
        $kasir = User::factory()->kasir()->create();
        $pelanggan = Pelanggan::factory()->create();

        $this->actingAs($kasir)
            ->get(route('pelanggan.edit', $pelanggan))
            ->assertForbidden();

        $this->actingAs($kasir)
            ->put(route('pelanggan.update', $pelanggan), [
                'nama_pelanggan' => 'Hack Name',
                'kota' => 'Jakarta',
            ])
            ->assertForbidden();

        $this->actingAs($kasir)
            ->delete(route('pelanggan.destroy', $pelanggan))
            ->assertForbidden();

        $this->assertDatabaseHas('tbl_pelanggan', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'nama_pelanggan' => $pelanggan->nama_pelanggan,
        ]);
    }

    public function test_admin_can_update_and_delete_pelanggan(): void
    {
        $admin = User::factory()->admin()->create();
        $pelanggan = Pelanggan::factory()->create();

        $this->actingAs($admin)
            ->put(route('pelanggan.update', $pelanggan), [
                'nama_pelanggan' => 'Pelanggan Updated',
                'kota' => 'Surabaya',
                'no_hp' => $pelanggan->no_hp,
            ])
            ->assertRedirect(route('pelanggan.index'));

        $this->assertDatabaseHas('tbl_pelanggan', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'nama_pelanggan' => 'Pelanggan Updated',
        ]);

        $this->actingAs($admin)
            ->delete(route('pelanggan.destroy', $pelanggan))
            ->assertRedirect(route('pelanggan.index'));

        $this->assertDatabaseMissing('tbl_pelanggan', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
        ]);
    }

    public function test_duplicate_no_hp_is_rejected(): void
    {
        $kasir = User::factory()->kasir()->create();
        Pelanggan::factory()->create(['no_hp' => '089999999999']);

        $this->actingAs($kasir)
            ->post(route('pelanggan.store'), [
                'nama_pelanggan' => 'Duplikat',
                'kota' => 'Madiun',
                'no_hp' => '089999999999',
            ])
            ->assertSessionHasErrors(['no_hp']);
    }
}

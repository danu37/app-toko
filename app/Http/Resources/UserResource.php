<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryCard = $this->relationLoaded('rfidCards')
            ? $this->rfidCards->firstWhere('uid', $this->rfid_uid) ?? $this->rfidCards->first()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'rfid_uid' => $this->rfid_uid ?: $primaryCard?->uid,
            'has_rfid' => ! empty($this->rfid_uid) || (bool) $primaryCard,
            'rfid_status' => $primaryCard?->status ?? (! empty($this->rfid_uid) ? 'aktif' : null),
            'id_cabang' => $this->id_cabang,
            'id_pelanggan' => $this->id_pelanggan,
            'is_active' => (bool) $this->is_active,
            'cabang' => CabangResource::make($this->whenLoaded('cabang')),
            'pelanggan' => $this->whenLoaded('pelanggan', fn () => $this->pelanggan ? [
                'id_pelanggan' => $this->pelanggan->id_pelanggan,
                'nama_pelanggan' => $this->pelanggan->nama_pelanggan,
                'no_hp' => $this->pelanggan->no_hp,
                'kota' => $this->pelanggan->kota,
                'alamat' => $this->pelanggan->alamat,
            ] : null),
            'rfid_cards' => $this->whenLoaded('rfidCards', fn () => $this->rfidCards->map(fn ($c) => [
                'id' => $c->id,
                'uid' => $c->uid,
                'status' => $c->status,
                'is_active' => $c->isActive(),
                'registered_at' => $c->registered_at?->toISOString(),
                'last_used_at' => $c->last_used_at?->toISOString(),
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

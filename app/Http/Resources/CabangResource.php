<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_cabang' => $this->id_cabang,
            'nama_cabang' => $this->nama_cabang,
            'kota' => $this->kota,
            'users_count' => $this->whenCounted('users'),
            'transaksi_count' => $this->whenCounted('transaksi'),
        ];
    }
}

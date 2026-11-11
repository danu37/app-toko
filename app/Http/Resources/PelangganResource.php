<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PelangganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pelanggan' => $this->id_pelanggan,
            'nama_pelanggan' => $this->nama_pelanggan,
            'kota' => $this->kota,
            'no_hp' => $this->no_hp,
            'alamat' => $this->alamat,
            'transaksi_count' => $this->whenCounted('transaksi'),
            'total_belanja_sum' => isset($this->transaksi_sum_total_belanja)
                ? (float) $this->transaksi_sum_total_belanja
                : null,
        ];
    }
}

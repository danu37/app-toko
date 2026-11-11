<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailTransaksiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $harga = (float) ($this->harga ?? 0);
        $jumlah = (int) ($this->jumlah ?? 0);

        return [
            'id_detail' => $this->id_detail,
            'id_transaksi' => $this->id_transaksi,
            'id_produk' => $this->id_produk,
            'nama_produk' => $this->nama_produk,
            'jumlah' => $jumlah,
            'harga' => $harga,
            'subtotal' => round($harga * $jumlah, 2),
            'produk' => ProdukResource::make($this->whenLoaded('produk')),
        ];
    }
}

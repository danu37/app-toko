<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $kasirSnapshot = filled($this->nama_kasir) ? (string) $this->nama_kasir : null;
        $loadedKasirName = $this->relationLoaded('user') ? $this->user?->name : null;

        return [
            'id_transaksi' => $this->id_transaksi,
            'kode_transaksi' => $this->kode_transaksi,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'id_pelanggan' => $this->id_pelanggan,
            'id_cabang' => $this->id_cabang,
            'user_id' => $this->user_id,
            'nama_kasir' => $kasirSnapshot ?? $loadedKasirName,
            'total_belanja' => (float) ($this->total_belanja ?? 0),
            'metode_pembayaran' => $this->metode_pembayaran,
            'uang_bayar' => $this->uang_bayar !== null ? (float) $this->uang_bayar : null,
            'kembalian' => $this->kembalian !== null ? (float) $this->kembalian : null,
            'status' => $this->status ?? 'selesai',
            'catatan' => $this->catatan,
            'detail_count' => $this->whenCounted('detail'),
            'pelanggan' => PelangganResource::make($this->whenLoaded('pelanggan')),
            'cabang' => CabangResource::make($this->whenLoaded('cabang')),
            'kasir' => UserResource::make($this->whenLoaded('user')),
            'detail' => DetailTransaksiResource::collection($this->whenLoaded('detail')),
        ];
    }
}

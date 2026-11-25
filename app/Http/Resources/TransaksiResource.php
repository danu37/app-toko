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
            'subtotal' => $this->subtotal !== null ? (float) $this->subtotal : null,
            'ppn_rate' => (float) ($this->ppn_rate ?? 0),
            'pajak' => (float) ($this->pajak ?? 0),
            'total_belanja' => (float) ($this->total_belanja ?? 0),
            'metode_pembayaran' => $this->metode_pembayaran,
            'bukti_pembayaran' => $this->bukti_pembayaran,
            'bukti_pembayaran_url' => $this->bukti_pembayaran
                ? asset('storage/'.ltrim($this->bukti_pembayaran, '/'))
                : null,
            'uang_bayar' => $this->uang_bayar !== null ? (float) $this->uang_bayar : null,
            'kembalian' => $this->kembalian !== null ? (float) $this->kembalian : null,
            'status' => $this->status ?? 'selesai',
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toISOString(),
            'catatan_verifikasi' => $this->catatan_verifikasi,
            'verifier' => UserResource::make($this->whenLoaded('verifier')),
            'catatan' => $this->catatan,
            'detail_count' => $this->whenCounted('detail'),
            'pelanggan' => PelangganResource::make($this->whenLoaded('pelanggan')),
            'cabang' => CabangResource::make($this->whenLoaded('cabang')),
            'kasir' => UserResource::make($this->whenLoaded('user')),
            'detail' => DetailTransaksiResource::collection($this->whenLoaded('detail')),
        ];
    }
}

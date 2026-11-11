<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdukResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_produk' => $this->id_produk,
            'id_kategori' => $this->id_kategori,
            'kode_produk' => $this->kode_produk,
            'nama_produk' => $this->nama_produk,
            'slug' => $this->slug,
            'harga' => (float) $this->harga,
            'stok' => (int) $this->stok,
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar,
            'gambar_url' => $this->gambar ? asset('storage/'.ltrim($this->gambar, '/')) : null,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'terjual_qty' => $this->when(array_key_exists('terjual_qty', $this->resource->getAttributes()), fn () => (int) ($this->terjual_qty ?? 0)),
            'kategori' => KategoriResource::make($this->whenLoaded('kategori')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KategoriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_kategori' => $this->id_kategori,
            'nama_kategori' => $this->nama_kategori,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar,
            'gambar_url' => $this->gambar ? asset('storage/'.ltrim($this->gambar, '/')) : null,
            'is_active' => (bool) $this->is_active,
            'produk_count' => $this->whenCounted('produk'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

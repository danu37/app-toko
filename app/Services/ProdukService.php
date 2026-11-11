<?php

namespace App\Services;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProdukService
{
    public function __construct(
        private readonly ImageCompressionService $imageCompressionService,
    ) {}

    public function create(array $data, ?UploadedFile $gambar = null): Produk
    {
        $payload = $data;
        $payload['slug'] = $this->generateUniqueProductSlug($data['nama_produk']);
        $payload['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $payload['is_active'] = (bool) ($data['is_active'] ?? true);

        if ($gambar) {
            $payload['gambar'] = $this->imageCompressionService->store($gambar, 'produk');
        } else {
            unset($payload['gambar']);
        }

        return Produk::create($payload);
    }

    public function update(Produk $produk, array $data, ?UploadedFile $gambar = null): Produk
    {
        $payload = $data;
        $oldImagePath = $produk->gambar;
        $newImagePath = null;

        if (isset($data['nama_produk']) && $data['nama_produk'] !== $produk->nama_produk) {
            $payload['slug'] = $this->generateUniqueProductSlug($data['nama_produk'], $produk->id_produk);
        }

        if ($gambar) {
            // Simpan file baru terlebih dahulu sebelum menghapus gambar lama agar file asli aman bila kompresi gagal.
            $newImagePath = $this->imageCompressionService->store($gambar, 'produk');
            $payload['gambar'] = $newImagePath;
        } else {
            unset($payload['gambar']);
        }

        $produk->update($payload);

        if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath && Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $produk->refresh();
    }

    public function delete(Produk $produk): void
    {
        $produk->delete();
    }

    public function createCategory(array $data, ?UploadedFile $gambar = null): Kategori
    {
        $payload = $data;
        $payload['slug'] = $this->generateUniqueCategorySlug($data['nama_kategori']);
        $payload['is_active'] = (bool) ($data['is_active'] ?? true);

        if ($gambar) {
            $payload['gambar'] = $this->imageCompressionService->store($gambar, 'kategori');
        } else {
            unset($payload['gambar']);
        }

        return Kategori::create($payload);
    }

    public function updateCategory(Kategori $kategori, array $data, ?UploadedFile $gambar = null): Kategori
    {
        $payload = $data;
        $oldImagePath = $kategori->gambar;
        $newImagePath = null;

        if (isset($data['nama_kategori']) && $data['nama_kategori'] !== $kategori->nama_kategori) {
            $payload['slug'] = $this->generateUniqueCategorySlug($data['nama_kategori'], $kategori->id_kategori);
        }

        if ($gambar) {
            // Simpan file baru terlebih dahulu sebelum menghapus gambar lama agar file asli aman bila kompresi gagal.
            $newImagePath = $this->imageCompressionService->store($gambar, 'kategori');
            $payload['gambar'] = $newImagePath;
        } else {
            unset($payload['gambar']);
        }

        $kategori->update($payload);

        if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath && Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $kategori->refresh();
    }

    public function generateUniqueProductSlug(string $nama, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($nama) ?: 'produk';
        $slug = $baseSlug;
        $counter = 1;

        while (
            Produk::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id_produk', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function generateUniqueCategorySlug(string $nama, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($nama) ?: 'kategori';
        $slug = $baseSlug;
        $counter = 1;

        while (
            Kategori::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id_kategori', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}

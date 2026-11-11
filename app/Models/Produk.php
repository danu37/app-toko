<?php

namespace App\Models;

use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_produk';

    protected $primaryKey = 'id_produk';

    public $timestamps = true;

    protected $fillable = [
        'id_kategori',
        'kode_produk',
        'nama_produk',
        'slug',
        'harga',
        'stok',
        'deskripsi',
        'gambar',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id_kategori' => 'integer',
            'harga' => 'decimal:2',
            'stok' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_produk', 'id_produk');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('nama_produk', 'like', $like)
                ->orWhere('kode_produk', 'like', $like);
        });
    }
}

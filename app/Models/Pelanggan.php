<?php

namespace App\Models;

use Database\Factories\PelangganFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    /** @use HasFactory<PelangganFactory> */
    use HasFactory;

    protected $table = 'tbl_pelanggan';

    protected $primaryKey = 'id_pelanggan';

    public $timestamps = false;

    protected $fillable = [
        'nama_pelanggan',
        'kota',
        'no_hp',
        'alamat',
    ];

    public function transaksi(): HasMany
    {
        return $this->hasMany(
            Transaksi::class,
            'id_pelanggan',
            'id_pelanggan'
        );
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('nama_pelanggan', 'like', $like)
                ->orWhere('kota', 'like', $like)
                ->orWhere('no_hp', 'like', $like);
        });
    }
}

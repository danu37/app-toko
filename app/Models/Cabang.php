<?php

namespace App\Models;

use Database\Factories\CabangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    /** @use HasFactory<CabangFactory> */
    use HasFactory;

    protected $table = 'tbl_cabang';

    protected $primaryKey = 'id_cabang';

    public $timestamps = false;

    protected $fillable = [
        'nama_cabang',
        'kota',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_cabang', 'id_cabang');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_cabang', 'id_cabang');
    }
}

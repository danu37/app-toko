<?php

namespace App\Models;

use Database\Factories\DetailTransaksiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksi extends Model
{
    /** @use HasFactory<DetailTransaksiFactory> */
    use HasFactory;

    protected $table = 'tbl_detail_transaksi';

    protected $primaryKey = 'id_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_transaksi',
        'id_produk',
        'nama_produk',
        'jumlah',
        'harga',
    ];

    protected function casts(): array
    {
        return [
            'id_transaksi' => 'integer',
            'id_produk' => 'integer',
            'jumlah' => 'integer',
            'harga' => 'decimal:2',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(
            Transaksi::class,
            'id_transaksi',
            'id_transaksi'
        );
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(
            Produk::class,
            'id_produk',
            'id_produk'
        )->withTrashed();
    }

    public function getSubtotalAttribute(): float
    {
        return round((float) $this->harga * (int) $this->jumlah, 2);
    }
}

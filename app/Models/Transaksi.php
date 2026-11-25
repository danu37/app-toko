<?php

namespace App\Models;

use Database\Factories\TransaksiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    /** @use HasFactory<TransaksiFactory> */
    use HasFactory;

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const PPN_RATE = 11.0;

    protected $table = 'tbl_transaksi';

    protected $primaryKey = 'id_transaksi';

    public $timestamps = false;

    protected $fillable = [
        'kode_transaksi',
        'tanggal',
        'id_pelanggan',
        'id_cabang',
        'user_id',
        'nama_kasir',
        'subtotal',
        'ppn_rate',
        'pajak',
        'total_belanja',
        'metode_pembayaran',
        'bukti_pembayaran',
        'uang_bayar',
        'kembalian',
        'status',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'subtotal' => 'decimal:2',
            'ppn_rate' => 'decimal:2',
            'pajak' => 'decimal:2',
            'total_belanja' => 'decimal:2',
            'uang_bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(
            Pelanggan::class,
            'id_pelanggan',
            'id_pelanggan'
        );
    }

    public function detail(): HasMany
    {
        return $this->hasMany(
            DetailTransaksi::class,
            'id_transaksi',
            'id_transaksi'
        );
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(
            Cabang::class,
            'id_cabang',
            'id_cabang'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by',
            'id'
        );
    }

    public function getBuktiPembayaranUrlAttribute(): ?string
    {
        if (blank($this->bukti_pembayaran)) {
            return null;
        }

        return asset('storage/'.ltrim($this->bukti_pembayaran, '/'));
    }
}

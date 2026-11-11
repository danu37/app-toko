<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    protected $table = 'tbl_log';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'aktivitas',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }
}

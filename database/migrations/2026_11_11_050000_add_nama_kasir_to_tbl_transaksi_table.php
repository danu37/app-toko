<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan snapshot nama kasir pada transaksi agar struk cetak dan histori
     * tetap mencatat nama kasir walaupun akun pengguna diubah atau dihapus.
     */
    public function up(): void
    {
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_transaksi', 'nama_kasir')) {
                $table->string('nama_kasir', 255)->nullable()->after('user_id');
            }
        });

        if (Schema::hasColumn('tbl_transaksi', 'user_id') && Schema::hasColumn('tbl_transaksi', 'nama_kasir')) {
            $userNames = DB::table('users')->pluck('name', 'id');

            if ($userNames->isNotEmpty()) {
                DB::table('tbl_transaksi')
                    ->whereNotNull('user_id')
                    ->whereNull('nama_kasir')
                    ->orderBy('id_transaksi')
                    ->get(['id_transaksi', 'user_id'])
                    ->each(function (object $row) use ($userNames): void {
                        $name = $userNames->get($row->user_id);

                        if ($name !== null) {
                            DB::table('tbl_transaksi')
                                ->where('id_transaksi', $row->id_transaksi)
                                ->update(['nama_kasir' => $name]);
                        }
                    });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_transaksi', 'nama_kasir')) {
                $table->dropColumn('nama_kasir');
            }
        });
    }
};


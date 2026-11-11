<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom pembayaran kasir (uang_bayar dan kembalian) secara nullable
     * agar data transaksi lama tetap terjaga utuh.
     */
    public function up(): void
    {
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_transaksi', 'uang_bayar')) {
                $table->decimal('uang_bayar', 15, 2)->nullable()->after('metode_pembayaran');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'kembalian')) {
                $table->decimal('kembalian', 15, 2)->nullable()->after('uang_bayar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            foreach (['kembalian', 'uang_bayar'] as $column) {
                if (Schema::hasColumn('tbl_transaksi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

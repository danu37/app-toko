<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghubungkan detail transaksi ke master tbl_produk secara nullable agar data lama tetap utuh.
     */
    public function up(): void
    {
        Schema::table('tbl_detail_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_detail_transaksi', 'id_produk')) {
                $table->unsignedBigInteger('id_produk')->nullable()->after('id_transaksi');
                $table->foreign('id_produk', 'fk_detail_transaksi_produk')
                    ->references('id_produk')
                    ->on('tbl_produk')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_detail_transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_detail_transaksi', 'id_produk')) {
                $table->dropForeign('fk_detail_transaksi_produk');
                $table->dropColumn('id_produk');
            }
        });
    }
};

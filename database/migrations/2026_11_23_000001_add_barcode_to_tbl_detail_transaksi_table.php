<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_detail_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_detail_transaksi', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('nama_produk');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_detail_transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_detail_transaksi', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }
};

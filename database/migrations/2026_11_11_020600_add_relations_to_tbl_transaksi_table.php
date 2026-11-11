<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melengkapi metadata transaksi (kode invoice, cabang, kasir/user, metode pembayaran, status, catatan)
     * tanpa merusak 24 data historis.
     */
    public function up(): void
    {
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_transaksi', 'kode_transaksi')) {
                $table->string('kode_transaksi', 50)->nullable()->unique()->after('id_transaksi');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'id_cabang')) {
                $table->integer('id_cabang')->nullable()->after('id_pelanggan');
                $table->foreign('id_cabang', 'fk_transaksi_cabang')
                    ->references('id_cabang')
                    ->on('tbl_cabang')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tbl_transaksi', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id_cabang');
                $table->foreign('user_id', 'fk_transaksi_user')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tbl_transaksi', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 30)->nullable()->after('total_belanja');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'status')) {
                $table->string('status', 20)->default('selesai')->after('metode_pembayaran')->index();
            }

            if (! Schema::hasColumn('tbl_transaksi', 'catatan')) {
                $table->text('catatan')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_transaksi', 'id_cabang')) {
                $table->dropForeign('fk_transaksi_cabang');
                $table->dropColumn('id_cabang');
            }

            if (Schema::hasColumn('tbl_transaksi', 'user_id')) {
                $table->dropForeign('fk_transaksi_user');
                $table->dropColumn('user_id');
            }

            foreach (['kode_transaksi', 'metode_pembayaran', 'status', 'catatan'] as $column) {
                if (Schema::hasColumn('tbl_transaksi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

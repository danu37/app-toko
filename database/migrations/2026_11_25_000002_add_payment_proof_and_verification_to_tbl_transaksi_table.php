<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_transaksi')) {
            return;
        }

        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_transaksi', 'bukti_pembayaran')) {
                $table->string('bukti_pembayaran', 255)->nullable()->after('metode_pembayaran');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('status');
                $table->foreign('verified_by', 'fk_transaksi_verified_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tbl_transaksi', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'catatan_verifikasi')) {
                $table->string('catatan_verifikasi', 255)->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_transaksi')) {
            return;
        }

        Schema::table('tbl_transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_transaksi', 'verified_by')) {
                $table->dropForeign('fk_transaksi_verified_by');
                $table->dropColumn('verified_by');
            }

            foreach (['catatan_verifikasi', 'verified_at', 'bukti_pembayaran'] as $column) {
                if (Schema::hasColumn('tbl_transaksi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

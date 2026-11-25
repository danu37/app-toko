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
            if (! Schema::hasColumn('tbl_transaksi', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->nullable()->after('nama_kasir');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'ppn_rate')) {
                $table->decimal('ppn_rate', 5, 2)->default(0)->after('subtotal');
            }

            if (! Schema::hasColumn('tbl_transaksi', 'pajak')) {
                $table->decimal('pajak', 15, 2)->default(0)->after('ppn_rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_transaksi')) {
            return;
        }

        Schema::table('tbl_transaksi', function (Blueprint $table) {
            foreach (['pajak', 'ppn_rate', 'subtotal'] as $column) {
                if (Schema::hasColumn('tbl_transaksi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

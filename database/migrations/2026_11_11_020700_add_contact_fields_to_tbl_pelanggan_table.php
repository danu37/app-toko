<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom kontak opsional pada tbl_pelanggan tanpa mengganggu data historis.
     */
    public function up(): void
    {
        Schema::table('tbl_pelanggan', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_pelanggan', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->unique()->after('kota');
            }

            if (! Schema::hasColumn('tbl_pelanggan', 'alamat')) {
                $table->text('alamat')->nullable()->after('no_hp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_pelanggan', function (Blueprint $table) {
            foreach (['no_hp', 'alamat'] as $column) {
                if (Schema::hasColumn('tbl_pelanggan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

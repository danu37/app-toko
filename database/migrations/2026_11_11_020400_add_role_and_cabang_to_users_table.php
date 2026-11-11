<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan role (admin/kasir), cabang penempatan, dan status aktif pada users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('kasir')->after('password')->index();
            }

            if (! Schema::hasColumn('users', 'id_cabang')) {
                $table->integer('id_cabang')->nullable()->after('role');
                $table->foreign('id_cabang', 'fk_users_cabang')
                    ->references('id_cabang')
                    ->on('tbl_cabang')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('id_cabang');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'id_cabang')) {
                $table->dropForeign('fk_users_cabang');
                $table->dropColumn('id_cabang');
            }

            foreach (['role', 'is_active'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

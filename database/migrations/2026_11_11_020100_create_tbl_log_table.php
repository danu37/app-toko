<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel tbl_log yang dirujuk oleh trigger trg_insert_pelanggan dan trg_update_pelanggan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tbl_log')) {
            Schema::create('tbl_log', function (Blueprint $table) {
                $table->integer('id_log', true);
                $table->text('aktivitas')->nullable();
                $table->dateTime('waktu')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_log');
    }
};

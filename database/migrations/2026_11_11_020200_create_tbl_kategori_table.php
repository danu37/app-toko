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
        if (! Schema::hasTable('tbl_kategori')) {
            Schema::create('tbl_kategori', function (Blueprint $table) {
                $table->id('id_kategori');
                $table->string('nama_kategori', 100);
                $table->string('slug', 120)->unique();
                $table->text('deskripsi')->nullable();
                $table->string('gambar', 255)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_kategori');
    }
};

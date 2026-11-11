<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melengkapi kolom produk (kategori, SKU, slug, gambar, status unggulan/aktif, soft delete).
     */
    public function up(): void
    {
        Schema::table('tbl_produk', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_produk', 'id_kategori')) {
                $table->unsignedBigInteger('id_kategori')->nullable()->after('id_produk');
                $table->foreign('id_kategori', 'fk_produk_kategori')
                    ->references('id_kategori')
                    ->on('tbl_kategori')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tbl_produk', 'kode_produk')) {
                $table->string('kode_produk', 50)->nullable()->unique()->after('id_kategori');
            }

            if (! Schema::hasColumn('tbl_produk', 'slug')) {
                $table->string('slug', 160)->nullable()->unique()->after('nama_produk');
            }

            if (! Schema::hasColumn('tbl_produk', 'gambar')) {
                $table->string('gambar', 255)->nullable()->after('deskripsi');
            }

            if (! Schema::hasColumn('tbl_produk', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('gambar')->index();
            }

            if (! Schema::hasColumn('tbl_produk', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_featured')->index();
            }

            if (! Schema::hasColumn('tbl_produk', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_produk', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_produk', 'id_kategori')) {
                $table->dropForeign('fk_produk_kategori');
                $table->dropColumn('id_kategori');
            }

            foreach (['kode_produk', 'slug', 'gambar', 'is_featured', 'is_active', 'deleted_at'] as $column) {
                if (Schema::hasColumn('tbl_produk', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

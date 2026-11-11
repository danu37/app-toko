<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memastikan tabel legacy (dari db-toko.sql) tersedia pada database baru/testing
     * tanpa menyentuh tabel atau data yang sudah ada di db-toko.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tbl_cabang')) {
            Schema::create('tbl_cabang', function (Blueprint $table) {
                $table->integer('id_cabang', true);
                $table->string('nama_cabang', 100)->nullable();
                $table->string('kota', 50)->nullable();
            });
        }

        if (! Schema::hasTable('tbl_pelanggan')) {
            Schema::create('tbl_pelanggan', function (Blueprint $table) {
                $table->integer('id_pelanggan', true);
                $table->string('nama_pelanggan', 100)->nullable();
                $table->string('kota', 50)->nullable();
            });
        }

        if (! Schema::hasTable('tbl_transaksi')) {
            Schema::create('tbl_transaksi', function (Blueprint $table) {
                $table->integer('id_transaksi', true);
                $table->date('tanggal')->nullable();
                $table->integer('id_pelanggan')->nullable();
                $table->decimal('total_belanja', 12, 2)->nullable();

                $table->index('id_pelanggan', 'id_pelanggan');
                $table->foreign('id_pelanggan', 'tbl_transaksi_ibfk_1')
                    ->references('id_pelanggan')
                    ->on('tbl_pelanggan');
            });
        }

        if (! Schema::hasTable('tbl_detail_transaksi')) {
            Schema::create('tbl_detail_transaksi', function (Blueprint $table) {
                $table->integer('id_detail', true);
                $table->integer('id_transaksi')->nullable();
                $table->string('nama_produk', 100)->nullable();
                $table->integer('jumlah')->nullable();
                $table->decimal('harga', 12, 2)->nullable();

                $table->index('id_transaksi', 'id_transaksi');
                $table->foreign('id_transaksi', 'tbl_detail_transaksi_ibfk_1')
                    ->references('id_transaksi')
                    ->on('tbl_transaksi');
            });
        }
    }

    /**
     * Sengaja tidak menghapus tabel legacy agar data eksisting tetap aman.
     */
    public function down(): void
    {
        //
    }
};

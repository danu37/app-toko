<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan index performa untuk query filter tanggal, pencarian, dan storefront
     * tanpa mengubah kolom atau data yang sudah ada.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_transaksi')) {
            Schema::table('tbl_transaksi', function (Blueprint $table) {
                if (! Schema::hasIndex('tbl_transaksi', 'idx_transaksi_tanggal_status')) {
                    $table->index(['tanggal', 'status'], 'idx_transaksi_tanggal_status');
                }
                if (! Schema::hasIndex('tbl_transaksi', 'idx_transaksi_metode')) {
                    $table->index('metode_pembayaran', 'idx_transaksi_metode');
                }
            });
        }

        if (Schema::hasTable('tbl_produk')) {
            Schema::table('tbl_produk', function (Blueprint $table) {
                if (! Schema::hasIndex('tbl_produk', 'idx_produk_active_featured')) {
                    $table->index(['is_active', 'is_featured'], 'idx_produk_active_featured');
                }
                if (! Schema::hasIndex('tbl_produk', 'idx_produk_nama')) {
                    $table->index('nama_produk', 'idx_produk_nama');
                }
            });
        }

        if (Schema::hasTable('tbl_pelanggan')) {
            Schema::table('tbl_pelanggan', function (Blueprint $table) {
                if (! Schema::hasIndex('tbl_pelanggan', 'idx_pelanggan_kota')) {
                    $table->index('kota', 'idx_pelanggan_kota');
                }
                if (! Schema::hasIndex('tbl_pelanggan', 'idx_pelanggan_nama')) {
                    $table->index('nama_pelanggan', 'idx_pelanggan_nama');
                }
            });
        }

        if (Schema::hasTable('tbl_detail_transaksi')) {
            Schema::table('tbl_detail_transaksi', function (Blueprint $table) {
                if (! Schema::hasIndex('tbl_detail_transaksi', 'idx_detail_nama_produk')) {
                    $table->index('nama_produk', 'idx_detail_nama_produk');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_transaksi')) {
            Schema::table('tbl_transaksi', function (Blueprint $table) {
                if (Schema::hasIndex('tbl_transaksi', 'idx_transaksi_tanggal_status')) {
                    $table->dropIndex('idx_transaksi_tanggal_status');
                }
                if (Schema::hasIndex('tbl_transaksi', 'idx_transaksi_metode')) {
                    $table->dropIndex('idx_transaksi_metode');
                }
            });
        }

        if (Schema::hasTable('tbl_produk')) {
            Schema::table('tbl_produk', function (Blueprint $table) {
                if (Schema::hasIndex('tbl_produk', 'idx_produk_active_featured')) {
                    $table->dropIndex('idx_produk_active_featured');
                }
                if (Schema::hasIndex('tbl_produk', 'idx_produk_nama')) {
                    $table->dropIndex('idx_produk_nama');
                }
            });
        }

        if (Schema::hasTable('tbl_pelanggan')) {
            Schema::table('tbl_pelanggan', function (Blueprint $table) {
                if (Schema::hasIndex('tbl_pelanggan', 'idx_pelanggan_kota')) {
                    $table->dropIndex('idx_pelanggan_kota');
                }
                if (Schema::hasIndex('tbl_pelanggan', 'idx_pelanggan_nama')) {
                    $table->dropIndex('idx_pelanggan_nama');
                }
            });
        }

        if (Schema::hasTable('tbl_detail_transaksi')) {
            Schema::table('tbl_detail_transaksi', function (Blueprint $table) {
                if (Schema::hasIndex('tbl_detail_transaksi', 'idx_detail_nama_produk')) {
                    $table->dropIndex('idx_detail_nama_produk');
                }
            });
        }
    }
};

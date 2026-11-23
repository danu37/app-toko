<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan id_pelanggan pada users jika belum tersedia
        if (! Schema::hasColumn('users', 'id_pelanggan')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('id_pelanggan')
                    ->nullable()
                    ->after('id_cabang');

                $table->foreign('id_pelanggan')
                    ->references('id_pelanggan')
                    ->on('tbl_pelanggan')
                    ->nullOnDelete();
            });
        }

        // 2. Buat tabel rfid_cards untuk mendukung manajemen kartu multi-role
        if (! Schema::hasTable('rfid_cards')) {
            Schema::create('rfid_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->string('uid', 100)->unique();
                $table->string('status', 20)->default('aktif'); // 'aktif' | 'nonaktif'
                $table->timestamp('registered_at')->useCurrent();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        // 3. Migrasikan kartu yang sudah ada dari users.rfid_uid ke rfid_cards
        if (Schema::hasColumn('users', 'rfid_uid')) {
            $existingUsers = DB::table('users')
                ->whereNotNull('rfid_uid')
                ->where('rfid_uid', '!=', '')
                ->get();

            foreach ($existingUsers as $user) {
                $trimmedUid = trim($user->rfid_uid);
                $exists = DB::table('rfid_cards')->where('uid', $trimmedUid)->exists();

                if (! $exists && ! empty($trimmedUid)) {
                    DB::table('rfid_cards')->insert([
                        'user_id' => $user->id,
                        'uid' => $trimmedUid,
                        'status' => 'aktif',
                        'registered_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_cards');

        if (Schema::hasColumn('users', 'id_pelanggan')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['id_pelanggan']);
                $table->dropColumn('id_pelanggan');
            });
        }
    }
};

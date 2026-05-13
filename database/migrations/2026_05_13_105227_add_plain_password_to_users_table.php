<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom plain_password ke tabel users.
 *
 * Kolom ini digunakan oleh Super Admin untuk melihat password
 * admin lembaga. Hanya diisi saat Super Admin membuat atau
 * mereset password admin — tidak pernah diisi oleh admin sendiri.
 *
 * Catatan keamanan: kolom ini hanya dapat diakses oleh Super Admin
 * dan tidak pernah diekspos ke endpoint publik manapun.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plain_password')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plain_password');
        });
    }
};
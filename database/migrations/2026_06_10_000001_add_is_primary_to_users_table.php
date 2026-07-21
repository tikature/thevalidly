<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 1 (tambahan) — Tandai Super Admin pertama/utama.
 *
 * Kolom is_primary hanya berlaku untuk role super_admin.
 * Akun super_admin pertama yang dibuat (via seeder) akan di-set is_primary = true.
 * Hanya satu akun yang boleh is_primary = true; akun ini tidak dapat
 * dinonaktifkan maupun dihapus oleh siapapun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
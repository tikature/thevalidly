<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan snapshot asset paths saat sertifikat digenerate.
 * Tujuan: PDF tetap render dengan asset asli meskipun lembaga
 * sudah ganti logo/ttd/cap/background setelahnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('snap_logo_path')->nullable()->after('cert_desc');
            $table->string('snap_ttd_path')->nullable()->after('snap_logo_path');
            $table->string('snap_cap_path')->nullable()->after('snap_ttd_path');
            $table->string('snap_bg_path')->nullable()->after('snap_cap_path');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['snap_logo_path', 'snap_ttd_path', 'snap_cap_path', 'snap_bg_path']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 4 — Tambah kolom qr_code ke tabel certificates.
 *
 * qr_code menyimpan data URI (base64 PNG) dari QR code yang mengarah
 * ke URL verifikasi publik. Disimpan di DB supaya:
 *  - Batch generate tidak perlu re-render QR tiap kali PDF diminta.
 *  - Konsisten antara PDF individual dan massal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Menyimpan data URI base64 PNG QR code (± 5–8 KB per record)
            $table->mediumText('qr_code')->nullable()->after('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('qr_code');
        });
    }
};

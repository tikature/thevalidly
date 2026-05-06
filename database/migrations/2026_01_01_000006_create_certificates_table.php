<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 2 — Tabel certificates.
 * Menyimpan metadata sertifikat. File PDF tidak disimpan di server,
 * melainkan di-generate on-demand via DomPDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama');
            $table->string('perusahaan')->nullable();
            $table->string('nomor');
            $table->string('event_name');
            $table->string('event_date');
            $table->string('event_place')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_title')->nullable();
            $table->string('cert_desc', 200)->nullable();
            $table->string('verification_token', 36)->unique();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};

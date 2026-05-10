<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 3 patch — Tambah unique index ke certificates.
 * Cegah duplikat nama+perusahaan dalam satu batch.
 *
 * Jalankan SETELAH php artisan batch:clean-duplicates
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan duplikat dulu sebelum tambah constraint
        // (jalankan: php artisan batch:clean-duplicates)

        Schema::table('certificates', function (Blueprint $table) {
            // Unique per batch: nama + perusahaan tidak boleh sama dalam 1 batch
            // batch_id nullable, jadi pakai partial index
            $table->index(['batch_id', 'nama'], 'idx_cert_batch_nama');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex('idx_cert_batch_nama');
        });
    }
};

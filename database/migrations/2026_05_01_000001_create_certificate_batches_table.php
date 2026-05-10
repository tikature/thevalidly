<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 3 — Tabel certificate_batches.
 * Untuk generate sertifikat massal via queue.
 * Catatan: tidak ada kolom lang — validly tidak menggunakan multi-bahasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tambah batch_id ke certificates (untuk relasi ke batch)
        Schema::table('certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('institution_id');
        });

        // Tabel batch
        Schema::create('certificate_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_name');
            $table->string('title', 255)->nullable();
            $table->string('event_date', 100);
            $table->string('event_place', 255)->nullable();
            $table->string('signer_name', 255)->nullable();
            $table->string('signer_title', 255)->nullable();
            $table->string('cert_desc', 200)->nullable();
            $table->uuid('batch_token')->unique();
            $table->integer('total')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('failed')->default(0);
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('processing');
            $table->json('failed_entries')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
        Schema::dropIfExists('certificate_batches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 1 — Tabel users.
 *
 * institution_id sengaja pakai unsignedBigInteger (bukan foreignId constrained)
 * sesuai desain asli proyek — tidak ada FK constraint agar admin tidak
 * ikut terhapus cascade saat lembaga dihapus. Pengecekan dilakukan di layer
 * aplikasi (SuperAdminController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin'])->default('admin');
            $table->unsignedBigInteger('institution_id')->nullable(); // tanpa FK constraint (by design)
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

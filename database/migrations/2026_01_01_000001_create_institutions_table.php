<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iterasi 1 — Tabel institutions.
 *
 * Kolom asset (logo_path, ttd_path, cap_path, background_path) sudah
 * disertakan meski belum dipakai di Iterasi 1, karena model Institution
 * mendaftarkannya di $fillable dan SuperAdminController mungkin menyentuhnya.
 * Ini mencegah SQL error "Unknown column" di MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();        // asset logo
            $table->string('ttd_path')->nullable();         // asset tanda tangan (Iterasi 2)
            $table->string('cap_path')->nullable();         // asset cap/stempel (Iterasi 2)
            $table->string('background_path')->nullable();  // asset background (Iterasi 2)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel background_library
 *
 * Menyimpan background sertifikat dalam 2 kategori:
 *  - System (is_system=true, institution_id=null)  : background bawaan Validly
 *  - Lembaga (is_system=false, institution_id=X)   : background yang diupload lembaga
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')
                  ->nullable()
                  ->constrained('institutions')
                  ->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('path');           // storage/public relative path
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_library');
    }
};

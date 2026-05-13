<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Ganti kolom event_date (string) dengan date_start + date_end (tipe DATE)
 * di tabel certificates dan certificate_batches.
 *
 * up()   : tambah date_start + date_end, isi dari event_date yang lama, hapus event_date
 * down() : kembalikan event_date string, isi dari date_start, hapus date columns
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── certificates ────────────────────────────────────────────
        Schema::table('certificates', function (Blueprint $table) {
            $table->date('date_start')->nullable()->after('event_name');
            $table->date('date_end')->nullable()->after('date_start');
        });

        // Migrasi data lama: coba parse tanggal dari string event_date
        // Data lama mungkin berbentuk "Held on 30-06-25 at Jakarta" atau string bebas
        // Sisakan null jika tidak bisa di-parse — lebih aman daripada nilai salah
        DB::statement("
            UPDATE certificates
            SET date_start = CASE
                WHEN event_date REGEXP '[0-9]{4}-[0-9]{2}-[0-9]{2}'
                    THEN REGEXP_SUBSTR(event_date, '[0-9]{4}-[0-9]{2}-[0-9]{2}')
                ELSE NULL
            END
        ");

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('event_date');
        });

        // ── certificate_batches ─────────────────────────────────────
        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->date('date_start')->nullable()->after('event_name');
            $table->date('date_end')->nullable()->after('date_start');
        });

        DB::statement("
            UPDATE certificate_batches
            SET date_start = CASE
                WHEN event_date REGEXP '[0-9]{4}-[0-9]{2}-[0-9]{2}'
                    THEN REGEXP_SUBSTR(event_date, '[0-9]{4}-[0-9]{2}-[0-9]{2}')
                ELSE NULL
            END
        ");

        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->dropColumn('event_date');
        });
    }

    public function down(): void
    {
        // ── certificates ────────────────────────────────────────────
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('event_date', 100)->nullable()->after('event_name');
        });

        // Rebuild string dari date_start saja (informasi date_end tidak bisa di-recover sempurna)
        DB::statement("
            UPDATE certificates
            SET event_date = CASE
                WHEN date_start IS NOT NULL
                    THEN CONCAT('Held on ', DATE_FORMAT(date_start, '%M %d, %Y'))
                ELSE ''
            END
        ");

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end']);
        });

        // ── certificate_batches ─────────────────────────────────────
        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->string('event_date', 100)->nullable()->after('event_name');
        });

        DB::statement("
            UPDATE certificate_batches
            SET event_date = CASE
                WHEN date_start IS NOT NULL
                    THEN CONCAT('Held on ', DATE_FORMAT(date_start, '%M %d, %Y'))
                ELSE ''
            END
        ");

        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end']);
        });
    }
};

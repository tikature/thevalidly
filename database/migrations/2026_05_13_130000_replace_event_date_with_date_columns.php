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
        $this->copyParsedDates('certificates');

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('event_date');
        });

        // ── certificate_batches ─────────────────────────────────────
        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->date('date_start')->nullable()->after('event_name');
            $table->date('date_end')->nullable()->after('date_start');
        });

        $this->copyParsedDates('certificate_batches');

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
        $this->restoreEventDates('certificates');

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end']);
        });

        // ── certificate_batches ─────────────────────────────────────
        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->string('event_date', 100)->nullable()->after('event_name');
        });

        $this->restoreEventDates('certificate_batches');

        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end']);
        });
    }

    private function copyParsedDates(string $table): void
    {
        foreach (DB::table($table)->select('id', 'event_date')->get() as $row) {
            if (!preg_match('/\d{4}-\d{2}-\d{2}/', (string) $row->event_date, $matches)) {
                continue;
            }

            $date = \DateTime::createFromFormat('!Y-m-d', $matches[0]);
            if ($date && $date->format('Y-m-d') === $matches[0]) {
                DB::table($table)->where('id', $row->id)->update([
                    'date_start' => $date->format('Y-m-d'),
                ]);
            }
        }
    }

    private function restoreEventDates(string $table): void
    {
        foreach (DB::table($table)->select('id', 'date_start')->get() as $row) {
            $eventDate = '';
            if ($row->date_start !== null) {
                $date = \DateTime::createFromFormat('!Y-m-d', (string) $row->date_start);
                if ($date) {
                    $eventDate = 'Held on ' . $date->format('F d, Y');
                }
            }

            DB::table($table)->where('id', $row->id)->update([
                'event_date' => $eventDate,
            ]);
        }
    }
};

<?php

namespace Database\Seeders;

use App\Models\BackgroundLibrary;
use Illuminate\Database\Seeder;

/**
 * SystemBackgroundSeeder
 *
 * Mendaftarkan background bawaan Validly ke tabel background_library.
 *
 * ────────────────────────────────────────────────────────
 * CARA MENAMBAH BACKGROUND BARU:
 * 1. Taruh file gambar di:
 *      public/storage/backgrounds/system/nama-file.jpg
 * 2. Tambahkan entry baru di array $backgrounds di bawah
 * 3. Jalankan:
 *      php artisan db:seed --class=SystemBackgroundSeeder
 *
 * CATATAN:
 * - Kolom `path` berisi path RELATIF dari public/storage/
 *   Contoh: 'backgrounds/system/elegant-gold.jpg'
 *   URL publik akan jadi: /storage/backgrounds/system/elegant-gold.jpg
 * - Seeder ini idempotent — aman dijalankan berkali-kali,
 *   tidak akan membuat duplikat (pakai firstOrCreate)
 * ────────────────────────────────────────────────────────
 *
 * Jalankan: php artisan db:seed --class=SystemBackgroundSeeder
 */
class SystemBackgroundSeeder extends Seeder
{
    /**
     * Daftar background bawaan Validly.
     *
     * Format: ['name' => 'Nama Tampil', 'file' => 'nama-file.jpg']
     * File harus ada di: public/storage/backgrounds/system/
     */
    private array $backgrounds = [
        // Tambahkan background baru di sini:
        // ['name' => 'Nama Background', 'file' => 'nama-file.jpg'],
        ['name' => 'Navy-Gold Validly', 'file' => 'Navy-Gold Validly.png'],
        ['name' => 'Gold-Brown Background', 'file' => 'Gold-Brown Bg.png'],
        
    ];

    public function run(): void
    {
        $added   = 0;
        $skipped = 0;

        foreach ($this->backgrounds as $bg) {
            $filePath   = public_path('storage/backgrounds/system/' . $bg['file']);
            $storagePath = 'backgrounds/system/' . $bg['file'];

            // Cek file fisik ada
            if (!file_exists($filePath)) {
                $this->command->warn("  ⚠ File tidak ditemukan, dilewati: {$bg['file']}");
                $skipped++;
                continue;
            }

            // firstOrCreate — tidak duplikat jika dijalankan ulang
            $created = BackgroundLibrary::firstOrCreate(
                ['path' => $storagePath, 'is_system' => true],
                ['name' => $bg['name'], 'institution_id' => null]
            );

            if ($created->wasRecentlyCreated) {
                $this->command->info("  ✓ Ditambahkan: {$bg['name']}");
                $added++;
            } else {
                $this->command->line("  – Sudah ada: {$bg['name']}");
                $skipped++;
            }
        }

        $this->command->newLine();
        $this->command->info("SystemBackgroundSeeder selesai: {$added} ditambahkan, {$skipped} dilewati.");

        if (empty($this->backgrounds)) {
            $this->command->warn('  Belum ada background yang didaftarkan.');
            $this->command->warn('  Taruh file JPG/PNG di public/storage/backgrounds/system/');
            $this->command->warn('  lalu daftarkan di array $backgrounds dalam seeder ini.');
        }
    }
}

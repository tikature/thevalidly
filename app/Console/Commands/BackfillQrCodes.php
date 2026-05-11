<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;

/**
 * Iterasi 4 — Backfill QR code untuk sertifikat yang sudah ada sebelumnya.
 *
 * Jalankan: php artisan app:backfill-qr-codes
 */
class BackfillQrCodes extends Command
{
    protected $signature   = 'app:backfill-qr-codes {--chunk=100 : Jumlah record per batch}';
    protected $description = 'Generate QR code untuk sertifikat yang belum punya qr_code (Iterasi 4)';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $total = Certificate::whereNull('qr_code')->count();

        if ($total === 0) {
            $this->info('Semua sertifikat sudah punya QR code. Tidak ada yang perlu di-backfill.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$total} sertifikat tanpa QR code. Mulai backfill...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed  = 0;

        Certificate::whereNull('qr_code')
            ->chunkById($chunk, function ($certificates) use ($bar, &$success, &$failed) {
                foreach ($certificates as $cert) {
                    [$ok, $error] = $this->processOne($cert);
                    if ($ok) {
                        $success++;
                    } else {
                        $failed++;
                        $this->newLine();
                        $this->warn("Gagal [{$cert->verification_token}]: {$error}");
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Berhasil: {$success} | Gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Proses satu sertifikat — dipisah agar bisa di-override di test.
     *
     * @return array{bool, string} [success, errorMessage]
     */
    protected function processOne(Certificate $cert): array
    {
        try {
            $cert->generateAndSaveQrCode();
            return [true, ''];
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Http\Controllers\CertificateController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command untuk cache PDF sertifikat yang belum ada di pdf_cache.
 *
 * Jalankan: php artisan certificates:cache-pdfs
 * Dry run:  php artisan certificates:cache-pdfs --dry-run
 */
class CacheMissingPdfs extends Command
{
    protected $signature   = 'certificates:cache-pdfs
                                {--dry-run : Tampilkan yang missing tanpa generate}
                                {--id=     : Cache hanya sertifikat dengan ID tertentu}';

    protected $description = 'Generate dan cache PDF untuk sertifikat yang belum ada di pdf_cache';

    public function handle(): int
    {
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Filter by ID jika ada
        $query = Certificate::with('institution');
        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        $certs   = $query->get();
        $total   = $certs->count();
        $missing = [];

        // Scan yang missing
        foreach ($certs as $cert) {
            $path = $cacheDir . DIRECTORY_SEPARATOR . $cert->verification_token . '.pdf';
            if (!file_exists($path) || filesize($path) === 0) {
                $missing[] = $cert;
            }
        }

        $missingCount = count($missing);

        $this->info("Total sertifikat : {$total}");
        $this->info("Sudah di-cache   : " . ($total - $missingCount));
        $this->warn("Belum di-cache   : {$missingCount}");

        if ($missingCount === 0) {
            $this->info('✓ Semua PDF sudah ada di cache.');
            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('-- DRY RUN (tidak generate) --');
            foreach ($missing as $cert) {
                $this->line("  MISSING [{$cert->id}] {$cert->nama} — {$cert->verification_token}");
            }
            return Command::SUCCESS;
        }

        // Konfirmasi sebelum generate
        if (!$this->confirm("Generate {$missingCount} PDF sekarang?", true)) {
            $this->info('Dibatalkan.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $bar     = $this->output->createProgressBar($missingCount);
        $success = 0;
        $failed  = 0;

        foreach ($missing as $cert) {
            $bar->setMessage("  [{$cert->nama}]");

            try {
                $institution = $cert->institution;

                // View warmup — hindari Windows file lock
                view('certificate.pdf', [
                    'certificate' => $cert,
                    'institution' => $institution,
                    'logoPath'    => $this->resolveAssetPath($institution->logo_path),
                    'ttdPath'     => $this->resolveAssetPath($institution->ttd_path),
                    'capPath'     => $this->resolveAssetPath($institution->cap_path),
                    'bgPath'      => $this->resolveAssetPath($institution->background_path),
                ])->render();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificate.pdf', [
                    'certificate' => $cert,
                    'institution' => $institution,
                    'logoPath'    => $this->resolveAssetPath($institution->logo_path),
                    'ttdPath'     => $this->resolveAssetPath($institution->ttd_path),
                    'capPath'     => $this->resolveAssetPath($institution->cap_path),
                    'bgPath'      => $this->resolveAssetPath($institution->background_path),
                ])
                ->setPaper([0, 0, 841.89, 595.28])
                ->setOptions([
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => false,
                    'defaultFont'             => 'DejaVu Serif',
                    'dpi'                     => 96,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'            => false,
                    'chroot'                  => str_replace('\\', '/', realpath(base_path())),
                ]);

                $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $cert->verification_token . '.pdf';
                file_put_contents($cachePath, $pdf->output());
                unset($pdf);
                gc_collect_cycles();

                $success++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  GAGAL [{$cert->id}] {$cert->nama}: " . $e->getMessage());
                Log::warning("CacheMissingPdfs gagal [{$cert->verification_token}]: " . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Berhasil : {$success}");
        if ($failed > 0) {
            $this->warn("✗ Gagal    : {$failed}");
        }

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function resolveAssetPath(?string $relativePath): string
    {
        if (!$relativePath) return '';
        $full = storage_path('app/public/' . $relativePath);
        return str_replace('\\', '/', $full);
    }
}

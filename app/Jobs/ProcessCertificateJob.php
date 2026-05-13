<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Timeout diperbesar karena sekarang generate PDF juga di sini
    public int $tries   = 1;
    public int $timeout = 500;

    public function __construct(
        private readonly CertificateBatch $batch,
        private readonly array            $participant,
        private readonly int              $index,
        private readonly array            $assetPaths = [],
    ) {}

    public function handle(): void
    {
        $nama = trim($this->participant['nama'] ?? '');

        if (empty($nama)) {
            $this->recordFailure('Unknown', 'Nama tidak valid');
            return;
        }

        // Guard duplikat
        $exists = Certificate::where('batch_id', $this->batch->id)
            ->where('nama', $nama)
            ->where('perusahaan', $this->participant['perusahaan'] ?? null)
            ->exists();

        if ($exists) {
            DB::table('certificate_batches')
                ->where('id', $this->batch->id)
                ->increment('processed');
            $this->checkCompletion();
            return;
        }

        try {
            $nomor = $this->participant['nomor'] ?? $this->generateNomor();

            $certificate = Certificate::create([
                'institution_id'     => $this->batch->institution_id,
                'batch_id'           => $this->batch->id,
                'issued_by'          => $this->batch->issued_by,
                'cert_desc'          => $this->batch->cert_desc,
                'nama'               => $nama,
                'perusahaan'         => $this->participant['perusahaan'] ?? null,
                'nomor'              => $nomor,
                'signer_name'        => $this->batch->signer_name,
                'signer_title'       => $this->batch->signer_title,
                'event_place'        => $this->batch->event_place,
                'event_name'         => $this->batch->event_name,
                'event_date'         => $this->batch->event_date,
                'verification_token' => (string) Str::uuid(),
            ]);

            // ── Generate PDF langsung ke cache ──────────────────────────
            $this->generatePdfToCache($certificate);

            DB::table('certificate_batches')
                ->where('id', $this->batch->id)
                ->increment('processed');

        } catch (\Exception $e) {
            $this->recordFailure($nama, $e->getMessage());
        }

        $this->checkCompletion();
    }

    /**
     * Generate PDF untuk sertifikat dan simpan ke pdf_cache/.
     * Kalau gagal, hanya di-log — tidak crash job (sertifikat tetap masuk DB).
     */
    private function generatePdfToCache(Certificate $certificate): void
    {
        try {
            $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $certificate->verification_token . '.pdf';

            $institution = Institution::find($this->batch->institution_id);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificate.pdf', [
                'certificate' => $certificate,
                'institution' => $institution,
                'logoPath'    => $this->assetPaths['logo'] ?? '',
                'ttdPath'     => $this->assetPaths['ttd']  ?? '',
                'capPath'     => $this->assetPaths['cap']  ?? '',
                'bgPath'      => $this->assetPaths['bg']   ?? '',
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

            file_put_contents($cachePath, $pdf->output());
            unset($pdf);
            gc_collect_cycles();

        } catch (\Exception $e) {
            // PDF gagal di-cache — sertifikat tetap ada di DB.
            // Download per-sertifikat akan generate on-demand seperti biasa.
            Log::warning("PDF cache gagal [{$certificate->verification_token}]: " . $e->getMessage());
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->recordFailure($this->participant['nama'] ?? 'Unknown', $e->getMessage());
        $this->checkCompletion();
    }

    private function recordFailure(string $nama, string $reason): void
    {
        try {
            DB::transaction(function () use ($nama, $reason) {
                $batch = CertificateBatch::lockForUpdate()->find($this->batch->id);
                if ($batch) {
                    $batch->increment('failed');
                    $batch->increment('processed');

                    $entries   = (array) ($batch->failed_entries ?? []);
                    $entries[] = ['nama' => $nama, 'reason' => $reason];
                    $batch->update(['failed_entries' => $entries]);
                }
            });
        } catch (\Exception $e) {
            DB::table('certificate_batches')
                ->where('id', $this->batch->id)
                ->update([
                    'failed'    => DB::raw('failed + 1'),
                    'processed' => DB::raw('processed + 1'),
                ]);
        }
    }

    private function checkCompletion(): void
    {
        try {
            DB::transaction(function () {
                $batch = CertificateBatch::lockForUpdate()->find($this->batch->id);
                if ($batch && $batch->status === 'processing' && $batch->processed >= $batch->total) {
                    $batch->update([
                        'status'      => 'done',
                        'finished_at' => now(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::warning('checkCompletion error: ' . $e->getMessage());
        }
    }

    private function generateNomor(): string
    {
        return 'CERT/' . str_pad($this->index + 1, 3, '0', STR_PAD_LEFT) . '/' . date('Y');
    }
}

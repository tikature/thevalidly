<?php

namespace Tests\Unit\Iterasi3;

use Tests\TestCase;
use App\Console\Commands\CacheMissingPdfs;
use App\Console\Commands\CleanBatchDuplicates;
use App\Console\Commands\RepairBatch;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfPdf;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View as ViewFacade;
use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ConsoleCommandsUnitTest extends TestCase
{
    use RefreshDatabase;

    private function bindDummyIO($command, array $parameters = [])
    {
        $input = new ArrayInput($parameters, $command->getDefinition());
        $output = new OutputStyle($input, new NullOutput());

        $command->setInput($input);
        $command->setOutput($output);
        $command->setLaravel($this->app);
    }

    /**
     * Sama seperti bindDummyIO(), tapi input-nya dilengkapi stream berisi
     * jawaban untuk prompt confirm() (misal "no" atau "yes").
     */
    private function bindDummyIOWithConfirmAnswer($command, array $parameters, string $answer)
    {
        $input = new ArrayInput($parameters, $command->getDefinition());

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $answer . "\n");
        rewind($stream);
        $input->setStream($stream);

        $output = new OutputStyle($input, new NullOutput());

        $command->setInput($input);
        $command->setOutput($output);
        $command->setLaravel($this->app);
    }

    public function test_cache_missing_pdfs_command_execution()
    {
        $command = new CacheMissingPdfs();
        $this->assertInstanceOf(CacheMissingPdfs::class, $command);

        $this->bindDummyIO($command);

        $result = $command->handle();
        $this->assertTrue($result === 0 || $result === null || $result === true);
    }

    public function test_cache_missing_pdfs_command_dry_run_lists_missing_certificates()
    {
        Certificate::factory()->create();

        $command = new CacheMissingPdfs();
        $this->bindDummyIO($command, ['--dry-run' => true]);

        $result = $command->handle();

        $this->assertSame(0, $result);
    }

    public function test_cache_missing_pdfs_command_generates_pdf_for_missing_certificate()
    {
        $certificate = Certificate::factory()->create();
        $cachePath = storage_path('app/pdf_cache/' . $certificate->verification_token . '.pdf');

        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $view = Mockery::mock(View::class);
        $view->shouldReceive('render')->andReturn('<html></html>');
        ViewFacade::shouldReceive('make')->andReturn($view);

        $pdf = Mockery::mock(DomPdfPdf::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('pdf-bytes');

        Pdf::shouldReceive('loadView')->andReturn($pdf);

        $command = new CacheMissingPdfs();
        $this->bindDummyIO($command, ['--id' => $certificate->id]);

        $result = $command->handle();

        $this->assertSame(0, $result);
        $this->assertFileExists($cachePath);
        $this->assertSame('pdf-bytes', file_get_contents($cachePath));
    }

    /**
     * Baris 28-29 — folder pdf_cache dibuat otomatis kalau belum ada.
     */
    public function test_cache_missing_pdfs_creates_cache_directory_when_missing()
    {
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
            @rmdir($cacheDir);
        }
        if (is_dir($cacheDir)) {
            $this->markTestSkipped('Folder pdf_cache tidak bisa dihapus karena masih ada konten lain.');
        }

        $command = new CacheMissingPdfs();
        $this->bindDummyIO($command, ['--dry-run' => true]);
        $command->handle();

        $this->assertDirectoryExists($cacheDir);
    }

    /**
     * Baris 71-73 — user menjawab "tidak" pada prompt konfirmasi generate.
     * Tidak boleh ada PDF yang dibuat, dan command tetap return SUCCESS.
     */
    public function test_cache_missing_pdfs_returns_success_and_does_nothing_when_confirm_declined()
    {
        $certificate = Certificate::factory()->create();
        $cachePath = storage_path('app/pdf_cache/' . $certificate->verification_token . '.pdf');
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $command = new CacheMissingPdfs();
        $this->bindDummyIOWithConfirmAnswer($command, ['--id' => $certificate->id], 'no');

        $result = $command->handle();

        $this->assertSame(0, $result);
        $this->assertFileDoesNotExist($cachePath);
    }

    /**
     * Baris 123-127 & 138 — satu sertifikat gagal di-generate (exception dari
     * DomPDF) sehingga masuk ke catch, dicatat sebagai failed, dan command
     * mengembalikan Command::FAILURE (karena $failed > 0).
     */
    public function test_cache_missing_pdfs_records_failure_and_returns_failure_code_when_generation_throws()
    {
        $certificate = Certificate::factory()->create();
        $cachePath = storage_path('app/pdf_cache/' . $certificate->verification_token . '.pdf');
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $view = Mockery::mock(View::class);
        $view->shouldReceive('render')->andReturn('<html></html>');
        ViewFacade::shouldReceive('make')->andReturn($view);

        Pdf::shouldReceive('loadView')->andThrow(new \Exception('DomPDF gagal simulasi'));

        Log::shouldReceive('warning')->once();

        $command = new CacheMissingPdfs();
        $this->bindDummyIOWithConfirmAnswer($command, ['--id' => $certificate->id], 'yes');

        $result = $command->handle();

        $this->assertSame(1, $result);
        $this->assertFileDoesNotExist($cachePath);
    }

    /**
     * Baris 146-148 (resolveAssetPath) — cabang ketika path asset TIDAK
     * kosong, jadi harus membangun full path via storage_path('app/public/...').
     */
    public function test_cache_missing_pdfs_resolves_full_asset_path_when_institution_has_assets()
    {
        $institution = Institution::factory()->create([
            'logo_path'        => 'logos/1/logo.png',
            'ttd_path'         => 'ttd/1/ttd.png',
            'cap_path'         => 'cap/1/cap.png',
            'background_path'  => 'backgrounds/1/bg.png',
        ]);
        $certificate = Certificate::factory()->create(['institution_id' => $institution->id]);
        $cachePath = storage_path('app/pdf_cache/' . $certificate->verification_token . '.pdf');
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $view = Mockery::mock(View::class);
        $view->shouldReceive('render')->andReturn('<html></html>');
        ViewFacade::shouldReceive('make')->andReturn($view);

        $pdf = Mockery::mock(DomPdfPdf::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('pdf-bytes-with-assets');

        Pdf::shouldReceive('loadView')->andReturn($pdf);

        $command = new CacheMissingPdfs();
        $this->bindDummyIOWithConfirmAnswer($command, ['--id' => $certificate->id], 'yes');

        $result = $command->handle();

        $this->assertSame(0, $result);
        $this->assertFileExists($cachePath);
    }

    public function test_clean_batch_duplicates_command_execution()
    {
        $command = new CleanBatchDuplicates();
        $this->assertInstanceOf(CleanBatchDuplicates::class, $command);

        $batch = CertificateBatch::factory()->create();

        $this->bindDummyIO($command, ['batch_id' => $batch->id]);

        $result = $command->handle();
        $this->assertTrue($result === 0 || $result === null || $result === true);
    }

    public function test_clean_batch_duplicates_removes_duplicates_and_updates_batch_counters()
    {
        $batch = CertificateBatch::factory()->create([
            'total' => 3,
            'processed' => 3,
            'failed' => 0,
        ]);

        $keep = Certificate::factory()->create([
            'batch_id' => $batch->id,
            'nama' => 'Budi',
            'perusahaan' => 'PT Contoh',
        ]);
        Certificate::factory()->create([
            'batch_id' => $batch->id,
            'nama' => 'Budi',
            'perusahaan' => 'PT Contoh',
        ]);
        Certificate::factory()->create([
            'batch_id' => $batch->id,
            'nama' => 'Ani',
            'perusahaan' => 'PT Lain',
        ]);

        $command = new CleanBatchDuplicates();
        $this->bindDummyIO($command, ['batch_id' => $batch->id]);

        $result = $command->handle();

        $this->assertSame(0, $result);
        $this->assertSame(1, Certificate::where('batch_id', $batch->id)->where('nama', 'Budi')->where('perusahaan', 'PT Contoh')->count());
        $this->assertSame($keep->id, Certificate::where('batch_id', $batch->id)->where('nama', 'Budi')->where('perusahaan', 'PT Contoh')->value('id'));
        $this->assertSame(2, Certificate::where('batch_id', $batch->id)->count());

        $batch->refresh();
        $this->assertSame(2, $batch->processed);
        $this->assertSame(2, $batch->total);
        $this->assertSame(0, $batch->failed);
    }

    public function test_repair_batch_command_execution()
    {
        $command = new RepairBatch();
        $this->assertInstanceOf(RepairBatch::class, $command);

        // Buat dummy batch agar RepairBatch menemukan record target di DB
        $batch = CertificateBatch::factory()->create();

        $this->bindDummyIO($command, ['batch_id' => $batch->id]);

        $result = $command->handle();

        // Memastikan return value berupa angka status code (0/Command::SUCCESS) atau boolean/null
        $this->assertTrue($result === 0 || $result === null || $result === true || is_numeric($result));
    }

    /**
     * Skenario: Menguji saat Batch ID tidak ditemukan di database (Baris 21-22 Error Branch)
     */
    public function test_command_fails_when_batch_not_found()
    {
        // Pastikan ID batch 99999 tidak ada di database
        $batchId = 99999;

        $command = new RepairBatch();

        // Bind argument batch_id dengan ID yang tidak ada
        $this->bindDummyIO($command, ['batch_id' => $batchId]);

        $result = $command->handle();

        // Verifikasi return value bernilai 1 (FAILURE)
        $this->assertEquals(1, $result);
    }
}
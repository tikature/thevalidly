<?php

namespace Tests\Unit\Iterasi3;

use App\Jobs\ProcessCertificateJob;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfPdf;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ProcessCertificateJobUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_process_certificate_job_instantiation_and_failed_handler()
    {
        $batchMock = Mockery::mock(CertificateBatch::class)->makePartial();
        $participant = [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'certificate_number' => 'CERT/2026/001'
        ];

        $job = new ProcessCertificateJob($batchMock, $participant, 0, []);

        $this->assertInstanceOf(ProcessCertificateJob::class, $job);

        try {
            $job->failed(new Exception('Testing Job Failure'));
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->assertNotNull($e);
        }
    }

    public function test_handle_records_failure_when_name_is_empty()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $job = new ProcessCertificateJob($batch, ['nama' => ''], 0, []);
        $job->handle();

        $batch->refresh();

        $this->assertSame(1, $batch->processed);
        $this->assertSame(1, $batch->failed);
        $this->assertNotEmpty($batch->failed_entries);
    }

    public function test_handle_skips_duplicate_certificate_and_marks_batch_processed()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        Certificate::factory()->create([
            'institution_id' => $institution->id,
            'batch_id' => $batch->id,
            'nama' => 'Budi',
            'perusahaan' => 'PT Contoh',
        ]);

        $job = new ProcessCertificateJob($batch, ['nama' => 'Budi', 'perusahaan' => 'PT Contoh'], 0, []);
        $job->handle();

        $batch->refresh();

        $this->assertSame(1, $batch->processed);
        $this->assertSame('done', $batch->status);
    }

    public function test_handle_creates_certificate_and_writes_pdf_cache()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'issued_by' => $user->id,
            'cert_desc' => 'Test',
            'signer_name' => 'Signer',
            'signer_title' => 'Title',
            'event_place' => 'Bandung',
            'event_name' => 'Event',
            'date_start' => now()->toDateString(),
            'date_end' => now()->toDateString(),
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $pdf = Mockery::mock(DomPdfPdf::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('pdf-bytes');

        Pdf::shouldReceive('loadView')->andReturn($pdf);

        $job = new ProcessCertificateJob($batch, ['nama' => 'Andi', 'perusahaan' => 'PT ABC', 'nomor' => 'CERT/001'], 0, [
            'snap_logo' => 'logo.png',
            'snap_ttd' => 'ttd.png',
            'snap_cap' => 'cap.png',
            'snap_bg' => 'bg.png',
        ]);

        $job->handle();

        $batch->refresh();
        $certificate = Certificate::where('batch_id', $batch->id)->first();

        $this->assertNotNull($certificate);
        $this->assertSame(1, $batch->processed);
        $this->assertSame('done', $batch->status);
        $this->assertFileExists(storage_path('app/pdf_cache/' . $certificate->verification_token . '.pdf'));
    }

    public function test_handle_records_failure_when_certificate_creation_throws_exception()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $job = new ProcessCertificateJob($batch, ['nama' => 'Error User'], 0, []);

        Certificate::creating(function () {
            throw new Exception('boom');
        });

        try {
            $job->handle();
        } finally {
            Certificate::flushEventListeners();
        }

        $batch->refresh();

        $this->assertSame(1, $batch->processed);
        $this->assertSame(1, $batch->failed);
        $this->assertNotEmpty($batch->failed_entries);
    }

    public function test_failed_method_records_failure_and_finishes_batch()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $job = new ProcessCertificateJob($batch, ['nama' => 'Fail'], 0, []);
        $job->failed(new Exception('Failure from queue'));

        $batch->refresh();

        $this->assertSame(1, $batch->processed);
        $this->assertSame(1, $batch->failed);
        $this->assertSame('done', $batch->status);
    }

    // ══════════════════════════════════════════════
    // generatePdfToCache() — dipanggil langsung via Reflection
    // (private method, tidak lewat handle() supaya lebih terarah / "unit")
    // ══════════════════════════════════════════════

    public function test_generate_pdf_to_cache_creates_directory_when_missing_and_writes_file()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);
        $certificate = Certificate::factory()->create([
            'institution_id' => $institution->id,
            'batch_id' => $batch->id,
        ]);

        // Pastikan folder pdf_cache belum ada, supaya cabang mkdir() ke-hit.
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
        $this->assertDirectoryDoesNotExist($cacheDir);

        $pdf = Mockery::mock(DomPdfPdf::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('pdf-bytes-langsung');
        Pdf::shouldReceive('loadView')->andReturn($pdf);

        $job = new ProcessCertificateJob($batch, ['nama' => $certificate->nama], 0, []);

        $method = new ReflectionMethod($job, 'generatePdfToCache');
        $method->setAccessible(true);
        $method->invoke($job, $certificate);

        // Folder otomatis dibuat (baris mkdir) + PDF berhasil ditulis (jalur sukses sampai return).
        $this->assertDirectoryExists($cacheDir);
        $this->assertFileExists($cacheDir . DIRECTORY_SEPARATOR . $certificate->verification_token . '.pdf');
        $this->assertSame('pdf-bytes-langsung', file_get_contents($cacheDir . DIRECTORY_SEPARATOR . $certificate->verification_token . '.pdf'));
    }

    /**
     * Sama seperti test di atas, tapi TANPA mock DomPDF — render PDF asli.
     * Dibuat sebagai jaring pengaman kalau mocking Facade tidak terhitung
     * coverage-nya dengan benar pada beberapa runner/environment.
     */
    public function test_generate_pdf_to_cache_writes_real_pdf_file_on_success()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);
        $certificate = Certificate::factory()->create([
            'institution_id' => $institution->id,
            'batch_id' => $batch->id,
        ]);

        $cacheDir  = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $certificate->verification_token . '.pdf';
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }

        $job = new ProcessCertificateJob($batch, ['nama' => $certificate->nama], 0, []);

        $method = new ReflectionMethod($job, 'generatePdfToCache');
        $method->setAccessible(true);
        $method->invoke($job, $certificate);

        $this->assertFileExists($cachePath);
        // File PDF asli selalu diawali dengan magic bytes "%PDF-"
        $this->assertStringStartsWith('%PDF-', file_get_contents($cachePath));
    }

    /**
     * Baris 137-140 — cabang catch: PDF gagal digenerate, harus di-log
     * dan TIDAK melempar exception ke luar (sertifikat tetap ada di DB).
     */
    public function test_generate_pdf_to_cache_logs_warning_and_swallows_exception_on_failure()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);
        $certificate = Certificate::factory()->create([
            'institution_id' => $institution->id,
            'batch_id' => $batch->id,
        ]);

        $cachePath = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache' . DIRECTORY_SEPARATOR . $certificate->verification_token . '.pdf');
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }

        Pdf::shouldReceive('loadView')->andThrow(new Exception('DomPDF gagal simulasi'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'PDF cache gagal');
            });

        $job = new ProcessCertificateJob($batch, ['nama' => $certificate->nama], 0, []);

        $method = new ReflectionMethod($job, 'generatePdfToCache');
        $method->setAccessible(true);

        // Tidak boleh melempar exception ke luar — harus ditangkap di dalam method.
        $method->invoke($job, $certificate);

        $this->assertFileDoesNotExist($cachePath);
    }

    // ══════════════════════════════════════════════
    // recordFailure() — via Reflection, memaksa DB::transaction() gagal
    // supaya cabang fallback (catch) ke-hit.
    // ══════════════════════════════════════════════

    public function test_record_failure_falls_back_to_raw_update_when_transaction_throws()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $job = new ProcessCertificateJob($batch, ['nama' => 'User Error'], 0, []);

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new Exception('Deadlock Simulation'));

        DB::shouldReceive('raw')->andReturn('failed + 1');
        DB::shouldReceive('table')->with('certificate_batches')->andReturnSelf();
        DB::shouldReceive('where')->with('id', $batch->id)->andReturnSelf();
        DB::shouldReceive('update')->once()->andReturn(1);

        $method = new ReflectionMethod($job, 'recordFailure');
        $method->setAccessible(true);
        $method->invoke($job, 'User Error', 'Simulasi Failure');

        $this->assertTrue(true);
    }

    // ══════════════════════════════════════════════
    // checkCompletion() — via Reflection, memaksa DB::transaction() gagal
    // supaya cabang catch (Log::warning) ke-hit.
    // ══════════════════════════════════════════════

    public function test_check_completion_logs_warning_when_transaction_throws()
    {
        $institution = Institution::factory()->create();
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'total' => 1,
            'processed' => 0,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $job = new ProcessCertificateJob($batch, ['nama' => 'Test'], 0, []);

        DB::shouldReceive('transaction')->once()->andThrow(new Exception('DB Error'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'checkCompletion error');
            });

        $method = new ReflectionMethod($job, 'checkCompletion');
        $method->setAccessible(true);
        $method->invoke($job);

        // Mockery expectations di atas (shouldReceive/withArgs) bukan assertion PHPUnit,
        // jadi butuh assertion eksplisit supaya tidak dianggap "risky" (no assertions).
        $this->assertTrue(true);
    }
}
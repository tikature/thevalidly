<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessCertificateJob;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: ProcessCertificateJob
 *
 * Menguji job secara langsung (tanpa HTTP) — integrasi penuh dengan DB dan PDF cache.
 *
 * Jalankan: php artisan test --filter ProcessCertificateJobTest
 */
class ProcessCertificateJobTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $user;
    private CertificateBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->institution = Institution::factory()->create();
        $this->user        = User::factory()->adminOf($this->institution)->create();

        $this->batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->user->id,
            'event_name'     => 'Pelatihan Job Test',
            'date_start'     => '2026-05-12',
            'date_end'       => null,
            'signer_name'    => 'Dr. Uji',
            'signer_title'   => 'Ketua',
            'total'          => 1,
            'processed'      => 0,
            'failed'         => 0,
            'status'         => 'processing',
        ]);
    }

    protected function tearDown(): void
    {
        $cacheDir = storage_path('app/pdf_cache');
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/*.pdf') as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    // ══════════════════════════════════════════════
    // Helper
    // ══════════════════════════════════════════════

    private function dispatch(array $participant, int $index = 0, array $assetPaths = []): void
    {
        $job = new ProcessCertificateJob($this->batch, $participant, $index, $assetPaths);
        $job->handle();
    }

    private function mockPdf(): void
    {
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('output')->andReturn('%PDF-1.4 fake-content');

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);
    }

    // ══════════════════════════════════════════════
    // Nama kosong — recordFailure
    // ══════════════════════════════════════════════

    #[Test]
    public function job_skips_and_records_failure_when_nama_is_empty(): void
    {
        $this->batch->update(['total' => 1]);

        $this->dispatch(['nama' => '   ', 'perusahaan' => null, 'nomor' => null]);

        $this->assertDatabaseMissing('certificates', ['batch_id' => $this->batch->id]);

        $batch = $this->batch->fresh();
        $this->assertEquals(1, $batch->failed);
        $this->assertNotEmpty($batch->failed_entries);
        $this->assertEquals('Nama tidak valid', $batch->failed_entries[0]['reason']);
    }

    #[Test]
    public function job_skips_and_records_failure_when_nama_is_null(): void
    {
        $this->dispatch(['nama' => null, 'perusahaan' => null, 'nomor' => null]);

        $batch = $this->batch->fresh();
        $this->assertEquals(1, $batch->failed);
    }

    #[Test]
    public function job_records_failure_entry_has_reason_key(): void
    {
        $this->dispatch(['nama' => '', 'nomor' => 'X']);

        $entries = $this->batch->fresh()->failed_entries;
        $this->assertNotEmpty($entries);
        $this->assertArrayHasKey('reason', $entries[0]);
    }

    // ══════════════════════════════════════════════
    // Happy path — certificate dibuat
    // ══════════════════════════════════════════════

    #[Test]
    public function job_creates_certificate_in_database(): void
    {
        $this->mockPdf();

        $this->dispatch(['nama' => 'Budi Santoso', 'perusahaan' => 'PT Maju', 'nomor' => 'CERT/001/2026']);

        $this->assertDatabaseHas('certificates', [
            'batch_id'       => $this->batch->id,
            'institution_id' => $this->institution->id,
            'nama'           => 'Budi Santoso',
            'perusahaan'     => 'PT Maju',
            'nomor'          => 'CERT/001/2026',
        ]);
    }

    #[Test]
    public function job_increments_processed_count(): void
    {
        $this->mockPdf();

        $this->dispatch(['nama' => 'Citra Dewi', 'perusahaan' => null, 'nomor' => null]);

        $this->assertEquals(1, $this->batch->fresh()->processed);
    }

    #[Test]
    public function job_copies_event_data_from_batch(): void
    {
        $this->mockPdf();

        $this->dispatch(['nama' => 'Dito Prasetyo', 'perusahaan' => null, 'nomor' => null]);

        $cert = Certificate::where('batch_id', $this->batch->id)->first();
        $this->assertEquals($this->batch->event_name, $cert->event_name);
        $this->assertEquals(
            $this->batch->date_start?->format('Y-m-d'),
            $cert->date_start?->format('Y-m-d')
        );
        $this->assertEquals($this->batch->signer_name, $cert->signer_name);
        $this->assertEquals($this->batch->signer_title, $cert->signer_title);
    }

    #[Test]
    public function job_generates_nomor_automatically_when_not_provided(): void
    {
        $this->mockPdf();

        $this->dispatch(['nama' => 'Eko Wijaya', 'perusahaan' => null, 'nomor' => null], index: 2);

        $cert = Certificate::where('batch_id', $this->batch->id)->first();
        $this->assertNotNull($cert->nomor);
        $this->assertStringContainsString('CERT/', $cert->nomor);
        $this->assertStringContainsString('003', $cert->nomor);
    }

    #[Test]
    public function job_uses_provided_nomor_when_given(): void
    {
        $this->mockPdf();

        $this->dispatch(['nama' => 'Fira Ayu', 'perusahaan' => null, 'nomor' => 'NOMOR/CUSTOM/001'], index: 0);

        $cert = Certificate::where('batch_id', $this->batch->id)->first();
        $this->assertEquals('NOMOR/CUSTOM/001', $cert->nomor);
    }

    #[Test]
    public function job_generates_unique_verification_token(): void
    {
        $this->mockPdf();

        $this->batch->update(['total' => 2]);

        $this->dispatch(['nama' => 'Peserta Satu', 'perusahaan' => null, 'nomor' => null], index: 0);
        $this->dispatch(['nama' => 'Peserta Dua',  'perusahaan' => null, 'nomor' => null], index: 1);

        $tokens = Certificate::where('batch_id', $this->batch->id)
            ->pluck('verification_token')
            ->unique();

        $this->assertCount(2, $tokens);
    }

    // ══════════════════════════════════════════════
    // Duplikat guard
    // ══════════════════════════════════════════════

    #[Test]
    public function job_skips_duplicate_participant_in_same_batch(): void
    {
        $this->mockPdf();

        $this->batch->update(['total' => 2]);

        $this->dispatch(['nama' => 'Gita Safitri', 'perusahaan' => 'PT X', 'nomor' => null], index: 0);
        $this->dispatch(['nama' => 'Gita Safitri', 'perusahaan' => 'PT X', 'nomor' => null], index: 1);

        $count = Certificate::where('batch_id', $this->batch->id)->where('nama', 'Gita Safitri')->count();
        $this->assertEquals(1, $count);
    }

    #[Test]
    public function duplicate_still_increments_processed(): void
    {
        $this->mockPdf();

        $this->batch->update(['total' => 2, 'processed' => 0]);

        $this->dispatch(['nama' => 'Hana Kartika', 'perusahaan' => 'PT Y', 'nomor' => null], index: 0);
        $this->dispatch(['nama' => 'Hana Kartika', 'perusahaan' => 'PT Y', 'nomor' => null], index: 1);

        $this->assertEquals(2, $this->batch->fresh()->processed);
    }

    // ══════════════════════════════════════════════
    // PDF cache
    // ══════════════════════════════════════════════

    #[Test]
    public function job_saves_pdf_to_cache_after_creating_certificate(): void
    {
        $this->mockPdf();

        $this->dispatch(['nama' => 'Ivan Hermawan', 'perusahaan' => null, 'nomor' => null]);

        $cert      = Certificate::where('batch_id', $this->batch->id)->first();
        $cachePath = storage_path('app/pdf_cache') . DIRECTORY_SEPARATOR . $cert->verification_token . '.pdf';

        $this->assertFileExists($cachePath);
    }

    #[Test]
    public function job_still_creates_certificate_when_pdf_cache_fails(): void
    {
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('render gagal'));
        Log::shouldReceive('warning')->once();

        $this->dispatch(['nama' => 'Joko Susilo', 'perusahaan' => null, 'nomor' => null]);

        $this->assertDatabaseHas('certificates', [
            'batch_id' => $this->batch->id,
            'nama'     => 'Joko Susilo',
        ]);
    }

    #[Test]
    public function pdf_cache_creates_directory_if_not_exists(): void
    {
        $this->mockPdf();

        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (is_dir($cacheDir)) {
            // Hapus semua file (bukan hanya *.pdf) agar rmdir berhasil
            foreach (glob($cacheDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                if (is_file($f)) @unlink($f);
            }
            @rmdir($cacheDir);
        }

        // Kalau masih ada (mungkin ada subfolder) — skip test ini
        if (is_dir($cacheDir)) {
            $this->markTestSkipped('Folder pdf_cache tidak bisa dihapus karena masih ada konten lain.');
        }

        $this->dispatch(['nama' => 'Novi Andriani', 'perusahaan' => null, 'nomor' => null]);

        $this->assertDirectoryExists($cacheDir);
    }

    // ══════════════════════════════════════════════
    // Status batch
    // ══════════════════════════════════════════════

    #[Test]
    public function batch_status_becomes_done_when_all_processed(): void
    {
        $this->mockPdf();

        $this->batch->update(['total' => 1, 'processed' => 0]);

        $this->dispatch(['nama' => 'Kiki Rahayu', 'perusahaan' => null, 'nomor' => null]);

        $this->assertEquals('done', $this->batch->fresh()->status);
        $this->assertNotNull($this->batch->fresh()->finished_at);
    }

    #[Test]
    public function batch_status_remains_processing_when_not_all_processed(): void
    {
        $this->mockPdf();

        $this->batch->update(['total' => 3, 'processed' => 0]);

        $this->dispatch(['nama' => 'Lina Marlina', 'perusahaan' => null, 'nomor' => null]);

        $this->assertEquals('processing', $this->batch->fresh()->status);
    }

    // ══════════════════════════════════════════════
    // failed() — job failure handler
    // ══════════════════════════════════════════════

    #[Test]
    public function failed_method_records_failure_and_checks_completion(): void
    {
        $this->batch->update(['total' => 1, 'processed' => 0]);

        $job = new ProcessCertificateJob(
            $this->batch,
            ['nama' => 'Maya Putri', 'perusahaan' => null, 'nomor' => null],
            0
        );

        $job->failed(new \Exception('Sesuatu error'));

        $batch = $this->batch->fresh();
        $this->assertEquals(1, $batch->failed);
        $this->assertEquals(1, $batch->processed);
        $this->assertEquals('done', $batch->status);
        $this->assertNotEmpty($batch->failed_entries);
    }

    // ══════════════════════════════════════════════
    // Catch — Certificate::create() throw (unique constraint)
    // ══════════════════════════════════════════════

    #[Test]
    public function handle_catch_records_failure_when_certificate_create_throws(): void
    {
        $existingToken = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

        Certificate::factory()->forInstitution($this->institution)->create([
            'verification_token' => $existingToken,
        ]);

        \Illuminate\Support\Str::createUuidsUsing(fn() => $existingToken);

        $this->mockPdf();

        $this->batch->update(['total' => 1]);

        $this->dispatch(['nama' => 'Trigger Catch', 'perusahaan' => null, 'nomor' => null]);

        \Illuminate\Support\Str::createUuidsNormally();

        $this->assertEquals(1, Certificate::count());
        $this->assertEquals(1, $this->batch->fresh()->failed);
    }

    // ══════════════════════════════════════════════
    // recordFailure() via ReflectionMethod
    // ══════════════════════════════════════════════

    #[Test]
    public function recordFailure_updates_failed_and_processed_counts(): void
    {
        $job = new ProcessCertificateJob(
            $this->batch,
            ['nama' => 'Fallback User', 'perusahaan' => null, 'nomor' => null],
            0
        );

        $failedBefore    = $this->batch->fresh()->failed;
        $processedBefore = $this->batch->fresh()->processed;

        $method = new \ReflectionMethod($job, 'recordFailure');
        $method->setAccessible(true);
        $method->invoke($job, 'Fallback User', 'test via reflection');

        $fresh = $this->batch->fresh();
        $this->assertEquals($failedBefore + 1, $fresh->failed);
        $this->assertEquals($processedBefore + 1, $fresh->processed);
        $this->assertNotEmpty($fresh->failed_entries);
    }

    #[Test]
    public function record_failure_calls_fallback_when_transaction_fails(): void
    {
        $job = new ProcessCertificateJob($this->batch, ['nama' => 'User Error'], 0);

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Deadlock Simulation'));

        DB::shouldReceive('raw')->andReturn('failed + 1');
        DB::shouldReceive('table')->with('certificate_batches')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('update')->once();

        $method = new \ReflectionMethod($job, 'recordFailure');
        $method->setAccessible(true);
        $method->invoke($job, 'User Error', 'Simulasi Failure');

        $this->assertTrue(true);
    }

    // ══════════════════════════════════════════════
    // checkCompletion() via ReflectionMethod
    // ══════════════════════════════════════════════

    #[Test]
    public function checkCompletion_does_not_change_status_when_already_done(): void
    {
        $this->batch->update(['status' => 'done', 'total' => 1, 'processed' => 1]);

        $job = new ProcessCertificateJob(
            $this->batch,
            ['nama' => 'Test', 'perusahaan' => null, 'nomor' => null],
            0
        );

        $method = new \ReflectionMethod($job, 'checkCompletion');
        $method->setAccessible(true);
        $method->invoke($job);

        $this->assertEquals('done', $this->batch->fresh()->status);
    }

    #[Test]
    public function checkCompletion_marks_batch_done_when_processed_reaches_total(): void
    {
        $this->batch->update(['status' => 'processing', 'total' => 1, 'processed' => 1]);

        $job = new ProcessCertificateJob(
            $this->batch,
            ['nama' => 'Test', 'perusahaan' => null, 'nomor' => null],
            0
        );

        $method = new \ReflectionMethod($job, 'checkCompletion');
        $method->setAccessible(true);
        $method->invoke($job);

        $this->assertEquals('done', $this->batch->fresh()->status);
        $this->assertNotNull($this->batch->fresh()->finished_at);
    }

    #[Test]
    public function check_completion_logs_warning_on_exception(): void
    {
        $job = new ProcessCertificateJob($this->batch, ['nama' => 'Test'], 0);

        DB::shouldReceive('transaction')->andThrow(new \Exception('DB Error'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'checkCompletion error');
            });

        $method = new \ReflectionMethod($job, 'checkCompletion');
        $method->setAccessible(true);
        $method->invoke($job);
    }

    // ══════════════════════════════════════════════
    // generatePdfToCache() — retry on file lock
    // ══════════════════════════════════════════════

    #[Test]
    public function pdf_cache_retries_on_file_lock_error_then_succeeds(): void
    {
        // Simulasi: attempt 1 → file lock error, attempt 2 → berhasil
        $callCount   = 0;
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('output')->andReturnUsing(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw new \Exception('Access is denied (code: 5)');
            }
            return '%PDF-1.4 fake-content';
        });

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'PDF cache retry'));

        Log::shouldReceive('warning')->never();

        $this->dispatch(['nama' => 'Retry Test', 'perusahaan' => null, 'nomor' => null]);

        // Sertifikat tetap dibuat di DB
        $this->assertDatabaseHas('certificates', [
            'batch_id' => $this->batch->id,
            'nama'     => 'Retry Test',
        ]);
    }

    #[Test]
    public function pdf_cache_retries_on_rename_error(): void
    {
        $callCount   = 0;
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('output')->andReturnUsing(function () use (&$callCount) {
            $callCount++;
            if ($callCount < 3) {
                throw new \Exception('rename(C:/tmp/abc.tmp,C:/views/abc.php): Access is denied');
            }
            return '%PDF-1.4 fake-content';
        });

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);

        Log::shouldReceive('info')
            ->times(2)
            ->withArgs(fn($msg) => str_contains($msg, 'PDF cache retry'));

        Log::shouldReceive('warning')->never();

        $this->dispatch(['nama' => 'Rename Error Test', 'perusahaan' => null, 'nomor' => null]);

        $this->assertDatabaseHas('certificates', [
            'batch_id' => $this->batch->id,
            'nama'     => 'Rename Error Test',
        ]);
    }

    #[Test]
    public function pdf_cache_logs_warning_after_all_retries_exhausted(): void
    {
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('output')->andThrow(
            new \Exception('Access is denied (code: 5)')
        );

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);

        Log::shouldReceive('info')
            ->times(2) // retry 1/3 dan 2/3
            ->withArgs(fn($msg) => str_contains($msg, 'PDF cache retry'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'PDF cache gagal'));

        $this->dispatch(['nama' => 'All Retry Failed', 'perusahaan' => null, 'nomor' => null]);

        // Sertifikat tetap ada di DB meski PDF gagal
        $this->assertDatabaseHas('certificates', [
            'batch_id' => $this->batch->id,
            'nama'     => 'All Retry Failed',
        ]);
    }

    #[Test]
    public function pdf_cache_non_filelock_error_does_not_retry(): void
    {
        // Error bukan file lock → langsung log warning tanpa retry
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('output')->andThrow(
            new \Exception('DomPDF: font not found')
        );

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);

        Log::shouldReceive('info')->never(); // tidak ada retry log
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'PDF cache gagal'));

        $this->dispatch(['nama' => 'Font Error Test', 'perusahaan' => null, 'nomor' => null]);

        $this->assertDatabaseHas('certificates', [
            'batch_id' => $this->batch->id,
            'nama'     => 'Font Error Test',
        ]);
    }
}

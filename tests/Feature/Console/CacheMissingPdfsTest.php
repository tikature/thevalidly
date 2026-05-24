<?php

namespace Tests\Feature\Console;

use App\Models\Certificate;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: CacheMissingPdfs Command
 * Jalankan: php artisan test --filter CacheMissingPdfsTest
 */
class CacheMissingPdfsTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private string      $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
        $this->cacheDir    = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (!is_dir($this->cacheDir)) mkdir($this->cacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*.pdf') ?: [] as $f) @unlink($f);
        parent::tearDown();
    }

    private function mockPdf(): void
    {
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 fake');
        Pdf::shouldReceive('loadView')->andReturn($pdf);
    }

    // ── dry-run ───────────────────────────────────────────────

    #[Test]
    public function dry_run_shows_correct_counts(): void
    {
        $cert1 = Certificate::factory()->forInstitution($this->institution)->create();
        $cert2 = Certificate::factory()->forInstitution($this->institution)->create();
        file_put_contents($this->cacheDir . '/' . $cert1->verification_token . '.pdf', 'cached');

        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true])
            ->expectsOutput('Total sertifikat : 2')
            ->expectsOutput('Sudah di-cache   : 1')
            ->assertExitCode(0);
    }

    #[Test]
    public function dry_run_does_not_generate_pdf(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $path = $this->cacheDir . '/' . $cert->verification_token . '.pdf';

        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true])->assertExitCode(0);

        $this->assertFileDoesNotExist($path);
    }

    #[Test]
    public function dry_run_shows_missing_entries(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true])
            ->expectsOutput('-- DRY RUN (tidak generate) --')
            ->assertExitCode(0);
    }

    #[Test]
    public function reports_all_cached_when_none_missing(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        file_put_contents($this->cacheDir . '/' . $cert->verification_token . '.pdf', 'cached');

        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true])
            ->expectsOutput('✓ Semua PDF sudah ada di cache.')
            ->assertExitCode(0);
    }

    #[Test]
    public function no_certificates_shows_all_cached(): void
    {
        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true])
            ->expectsOutput('Total sertifikat : 0')
            ->expectsOutput('✓ Semua PDF sudah ada di cache.')
            ->assertExitCode(0);
    }

    #[Test]
    public function filter_by_id_limits_scope(): void
    {
        $cert1 = Certificate::factory()->forInstitution($this->institution)->create();
        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true, '--id' => $cert1->id])
            ->expectsOutput('Total sertifikat : 1')
            ->assertExitCode(0);
    }

    // ── mkdir baris 29 ───────────────────────────────────────

    #[Test]
    public function creates_cache_directory_if_not_exists(): void
    {
        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                if (is_file($f)) @unlink($f);
            }
            @rmdir($this->cacheDir);
        }

        if (is_dir($this->cacheDir)) {
            $this->markTestSkipped('Folder pdf_cache tidak bisa dihapus.');
        }

        $this->artisan('certificates:cache-pdfs', ['--dry-run' => true])->assertExitCode(0);
        $this->assertDirectoryExists($this->cacheDir);
    }

    // ── konfirmasi ditolak baris 71-73 ───────────────────────

    #[Test]
    public function cancels_when_user_answers_no_to_confirm(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'no')
            ->expectsOutput('Dibatalkan.')
            ->assertExitCode(0);
    }

    // ── generate flow baris 76-149 ───────────────────────────

    #[Test]
    public function generates_pdf_and_writes_to_cache(): void
    {
        $this->mockPdf();
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $cert->verification_token . '.pdf';

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'yes')
            ->expectsOutput('✓ Berhasil : 1')
            ->assertExitCode(0);

        $this->assertFileExists($path);
    }

    #[Test]
    public function returns_success_exit_code_when_all_succeed(): void
    {
        $this->mockPdf();
        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'yes')
            ->assertExitCode(0);
    }

    #[Test]
    public function returns_failure_exit_code_when_some_certs_fail(): void
    {
        // institution_id NOT NULL di DB — skenario orphan tidak mungkin terjadi.
        // Test ini cover exit code FAILURE saat ada sertifikat gagal di-generate.
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('PDF Error'));
        Log::shouldReceive('warning')->once();

        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'yes')
            ->assertExitCode(1); // Command::FAILURE karena failed > 0
    }

    #[Test]
    public function logs_warning_when_pdf_generation_fails(): void
    {
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('DomPDF error'));
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'CacheMissingPdfs gagal'));

        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'yes')
            ->expectsOutputToContain('GAGAL')
            ->assertExitCode(1);
    }

    #[Test]
    public function shows_failed_count_in_summary(): void
    {
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('Error'));
        Log::shouldReceive('warning')->once();

        Certificate::factory()->forInstitution($this->institution)->create();

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'yes')
            ->expectsOutputToContain('Gagal')
            ->assertExitCode(1);
    }

    #[Test]
    public function generates_pdf_with_institution_assets(): void
    {
        // Cover resolveAssetPath baris 155-156 — path non-null
        $this->mockPdf();

        // Set asset paths di institution
        $this->institution->update([
            'logo_path'       => 'institutions/1/logo/logo.png',
            'ttd_path'        => 'institutions/1/ttd/ttd.png',
            'cap_path'        => 'institutions/1/cap/cap.png',
            'background_path' => 'institutions/1/background/bg.jpg',
        ]);

        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $cert->verification_token . '.pdf';

        $this->artisan('certificates:cache-pdfs')
            ->expectsConfirmation('Generate 1 PDF sekarang?', 'yes')
            ->expectsOutput('✓ Berhasil : 1')
            ->assertExitCode(0);

        $this->assertFileExists($path);
    }
}

<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: CertificateController
 *
 * Mencakup: pdf() cache hit/miss, historyBatch() search & sort,
 * destroy() cache cleanup, resolveAssetPath()
 *
 * Jalankan: php artisan test --filter CertificateControllerTest
 */
class CertificateControllerTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $this->institution = Institution::factory()->create();
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    protected function tearDown(): void
    {
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $f) {
                @unlink($f);
            }
        }

        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (is_dir($tempDir)) {
            foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $f) {
                @unlink($f);
            }
        }

        parent::tearDown();
    }

    private function mockPdf(): void
    {
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('download')->andReturn(
            response('%PDF-1.4 fake', 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="sertifikat.pdf"',
            ])
        );
        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);
    }

    // ══════════════════════════════════════════════
    // pdf() — cache HIT: filename dibuat dari nama+nomor
    // ══════════════════════════════════════════════

    #[Test]
    public function pdf_filename_built_from_nama_slug_and_nomor(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'nama'  => 'Budi Santoso',
            'nomor' => 'CERT/001/2026',
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('pdf_cache/' . $cert->verification_token . '.pdf', '%PDF-1.4');

        $response = $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token));

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('budi-santoso', $disposition);
        $this->assertStringContainsString('CERT-001-2026', $disposition);
    }

    #[Test]
    public function pdf_nomor_with_backslash_is_sanitized_in_filename(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'nama'  => 'Citra Dewi',
            'nomor' => 'CERT\\002\\2026',
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('pdf_cache/' . $cert->verification_token . '.pdf', '%PDF-1.4');

        $response = $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token));

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringNotContainsString('\\', $disposition);
    }

    #[Test]
    public function pdf_fallback_generates_on_the_fly_when_no_cache(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->mockPdf();

        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function progress_zip_filename_extracts_batch_number_from_title(): void
    {
        // Kita beri title yang mengandung angka setelah kata "Batch"
        // agar baris $batchNo = $m[1] tereksekusi.
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'event_name'     => 'Webinar Nasional', 
            'title'          => 'Webinar Nasional - Batch 5', // Ada angka 5
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk();

        // Pastikan nama file mengandung 'Batch5' hasil ekstraksi regex
        $this->assertStringContainsString('Batch5', $response->json('zip_filename'));
        
        // Pastikan slug event_name juga benar
        $this->assertStringContainsString('webinar_nasional', $response->json('zip_filename'));
    }
    // ══════════════════════════════════════════════
    // historyBatch() — search & sort
    // ══════════════════════════════════════════════

    #[Test]
    public function history_batch_search_filters_by_title(): void
    {
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'title'          => 'Pelatihan Khusus - Batch 1',
            'event_name'     => 'Pelatihan Khusus',
        ]);
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'title'          => 'Workshop Umum - Batch 1',
            'event_name'     => 'Workshop Umum',
        ]);

        $this->actingAs($this->admin)
            ->get(route('certificate.history.batch', ['search' => 'Khusus']))
            ->assertOk()
            ->assertSee('Pelatihan Khusus')
            ->assertDontSee('Workshop Umum');
    }

    #[Test]
    public function history_batch_search_filters_by_event_name(): void
    {
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'title'          => null,
            'event_name'     => 'Seminar Nasional',
        ]);
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'title'          => null,
            'event_name'     => 'Pelatihan Lokal',
        ]);

        $this->actingAs($this->admin)
            ->get(route('certificate.history.batch', ['search' => 'Nasional']))
            ->assertOk()
            ->assertSee('Seminar Nasional')
            ->assertDontSee('Pelatihan Lokal');
    }

    #[Test]
    public function history_batch_sort_by_event_date(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.history.batch', ['sort_by' => 'event', 'sort' => 'asc']))
            ->assertOk();
    }

    // ══════════════════════════════════════════════
    // destroy() — cache cleanup
    // ══════════════════════════════════════════════

    #[Test]
    public function destroy_deletes_pdf_cache_when_it_exists(): void
    {
        Storage::fake('local');

        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        Storage::disk('local')->put('pdf_cache/' . $cert->verification_token . '.pdf', '%PDF-fake');
        Storage::disk('local')->assertExists('pdf_cache/' . $cert->verification_token . '.pdf');

        $this->actingAs($this->admin)
            ->delete(route('certificate.destroy', $cert))
            ->assertRedirect();

        Storage::disk('local')->assertMissing('pdf_cache/' . $cert->verification_token . '.pdf');
        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
    }

    // ══════════════════════════════════════════════
    // resolveAssetPath()
    // ══════════════════════════════════════════════

    #[Test]
    public function resolve_asset_path_returns_full_path_without_backslash(): void
    {
        $institution = Institution::factory()->create([
            'logo_path'       => 'institutions/1/logo/test.png',
            'ttd_path'        => 'institutions/1/ttd/test.png',
            'cap_path'        => null,
            'background_path' => null,
        ]);
        $admin = User::factory()->adminOf($institution)->create();
        $cert  = Certificate::factory()->forInstitution($institution)->create();

        $this->mockPdf();

        $this->actingAs($admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertOk();

        $controller = new \App\Http\Controllers\CertificateController();
        $method = new \ReflectionMethod($controller, 'resolveAssetPath');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'institutions/1/logo/test.png');
        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('institutions/1/logo/test.png', $result);
    }
}

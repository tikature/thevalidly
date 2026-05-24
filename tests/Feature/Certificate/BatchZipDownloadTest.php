<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Batch ZIP Download — skenario respons unik
 *
 * Scope file ini: skenario yang TIDAK ada di CertificateBatchControllerTest:
 * - Batch tanpa sertifikat → 404
 * - Batch tanpa PDF cached → 409
 * - ZIP filename mengandung tanggal (format Ymd, misal 20260509)
 * - ZIP filename mengandung batch number (format Batch{N})
 * - Content-Type header application/zip
 * - Content-Disposition attachment
 *
 * Catatan: Controller menggunakan storage_path('app/pdf_cache') secara langsung
 * (bukan Storage facade), sehingga Storage::fake() tidak berlaku.
 * Test yang butuh PDF fisik menulis ke path nyata dan membersihkannya di tearDown.
 *
 * Jalankan: php artisan test --filter BatchZipDownloadTest
 */
class BatchZipDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Institution      $institution;
    private User             $admin;
    private CertificateBatch $batch;

    /** Token PDF yang dibuat selama test, untuk dihapus di tearDown. */
    private array $createdPdfTokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->admin       = User::factory()->adminOf($this->institution)->create();
        $this->batch       = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'done',
            'event_name'     => 'Workshop Nasional',
            'title'          => 'Workshop Nasional - Batch 1',
        ]);
    }

    protected function tearDown(): void
    {
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        foreach ($this->createdPdfTokens as $token) {
            $path = $cacheDir . DIRECTORY_SEPARATOR . $token . '.pdf';
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
    }

    // ── Helper: tulis PDF dummy ke path fisik yang dipakai controller ──

    private function fakePdfCache(string $token): void
    {
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . $token . '.pdf', '%PDF-1.4 dummy');
        $this->createdPdfTokens[] = $token;
    }

    // ── Kondisi error ──────────────────────────────────────────

    #[Test]
    public function returns_404_when_batch_has_no_certificates(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.batch.zip', $this->batch->batch_token))
            ->assertStatus(404);
    }

    #[Test]
    public function returns_409_when_no_pdfs_are_cached(): void
    {
        Certificate::factory()->count(2)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $this->batch->id,
        ]);

        // Sertifikat ada tapi tidak ada file PDF di cache

        $this->actingAs($this->admin)
            ->get(route('certificate.batch.zip', $this->batch->batch_token))
            ->assertStatus(409);
    }

    // ── Format filename ────────────────────────────────────────

    #[Test]
    public function zip_filename_contains_current_date(): void
    {
        // Controller format tanggal: Ymd (contoh: 20260509), bukan Y-m-d
        $cert = Certificate::factory()->create([
            'institution_id'     => $this->institution->id,
            'batch_id'           => $this->batch->id,
            'verification_token' => 'tok-date-test',
        ]);
        $this->fakePdfCache($cert->verification_token);

        $response = $this->actingAs($this->admin)
            ->get(route('certificate.batch.zip', $this->batch->batch_token));

        $disposition = $response->headers->get('Content-Disposition', '');
        $this->assertStringContainsString(now()->format('Ymd'), $disposition);
    }

    #[Test]
    public function zip_filename_contains_batch_number(): void
    {
        // Controller ekstrak angka dari title "Event - Batch {N}" → "...Batch{N}.zip"
        $batch2 = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'event_name'     => 'Workshop Nasional',
            'title'          => 'Workshop Nasional - Batch 3',
            'status'         => 'done',
        ]);

        $cert = Certificate::factory()->create([
            'institution_id'     => $this->institution->id,
            'batch_id'           => $batch2->id,
            'verification_token' => 'tok-batchno-test',
        ]);
        $this->fakePdfCache($cert->verification_token);

        $response = $this->actingAs($this->admin)
            ->get(route('certificate.batch.zip', $batch2->batch_token));

        $disposition = $response->headers->get('Content-Disposition', '');
        // Format di filename: "Batch3" (tanpa dash, sesuai controller)
        $this->assertMatchesRegularExpression('/Batch\d+/i', $disposition);
    }

    // ── Response headers ───────────────────────────────────────

    #[Test]
    public function zip_content_type_is_application_zip(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id'     => $this->institution->id,
            'batch_id'           => $this->batch->id,
            'verification_token' => 'tok-ct-test',
        ]);
        $this->fakePdfCache($cert->verification_token);

        $response = $this->actingAs($this->admin)
            ->get(route('certificate.batch.zip', $this->batch->batch_token));

        $this->assertStringContainsString('application/zip', $response->headers->get('Content-Type', ''));
    }

    #[Test]
    public function response_has_content_disposition_attachment(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id'     => $this->institution->id,
            'batch_id'           => $this->batch->id,
            'verification_token' => 'tok-disp-test',
        ]);
        $this->fakePdfCache($cert->verification_token);

        $response = $this->actingAs($this->admin)
            ->get(route('certificate.batch.zip', $this->batch->batch_token));

        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition', ''));
    }
}

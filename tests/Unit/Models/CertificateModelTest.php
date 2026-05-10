<?php

namespace Tests\Unit\Models;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Certificate Model — tambahan untuk cover baris yang belum tercover
 *
 * Melengkapi CertificateModelTest.php yang sudah ada.
 * Mencakup: relasi batch(), participantUrl(), pdfUrl()
 *
 * Jalankan: php artisan test --filter CertificateModelExtraTest
 */
class CertificateModelTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private CertificateBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();

        $this->batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
    }

    // ══════════════════════════════════════════════
    // Baris 46: relasi batch()
    // ══════════════════════════════════════════════

    #[Test]
    public function certificate_belongs_to_batch(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'batch_id' => $this->batch->id,
        ]);

        $this->assertInstanceOf(CertificateBatch::class, $cert->batch);
        $this->assertEquals($this->batch->id, $cert->batch->id);
    }

    #[Test]
    public function certificate_batch_is_null_when_not_in_a_batch(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'batch_id' => null,
        ]);

        $this->assertNull($cert->batch);
    }

    // ══════════════════════════════════════════════
    // participantUrl() — belum pernah ditest
    // ══════════════════════════════════════════════

    #[Test]
    public function participant_url_contains_token(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertStringContainsString($cert->verification_token, $cert->participantUrl());
    }

    #[Test]
    public function participant_url_contains_cert_path(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertStringContainsString('/cert/', $cert->participantUrl());
    }

    // ══════════════════════════════════════════════
    // pdfUrl() — belum pernah ditest
    // ══════════════════════════════════════════════

    #[Test]
    public function pdf_url_contains_token(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertStringContainsString($cert->verification_token, $cert->pdfUrl());
    }

    #[Test]
    public function pdf_url_contains_pdf_path(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertStringContainsString('/pdf', $cert->pdfUrl());
    }

    #[Test]
    public function pdf_url_is_different_from_verification_url(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertNotEquals($cert->verificationUrl(), $cert->pdfUrl());
    }
}
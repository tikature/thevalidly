<?php

namespace Tests\Unit\Iterasi4;

use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Certificate Model — Verifikasi & QR Code — Iterasi 4
 *
 * Lingkup: auto-generate verification_token, generateAndSaveQrCode(),
 * getQrCodeDataUri(), verificationUrl(), participantUrl(), pdfUrl().
 * Sesuai US-18 (QR Code pada Sertifikat) & US-19 (Halaman Verifikasi Publik).
 *
 * Jalankan: php artisan test --filter CertificateVerificationTest
 */
class CertificateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
    }

    // ══════════════════════════════════════════════
    // verification_token — auto generate saat creating()
    // ══════════════════════════════════════════════

    #[Test]
    public function verification_token_is_auto_generated_when_not_provided(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'verification_token' => null,
        ]);

        $this->assertNotEmpty($cert->verification_token);
        $this->assertTrue(\Illuminate\Support\Str::isUuid($cert->verification_token));
    }

    #[Test]
    public function verification_token_is_preserved_when_explicitly_provided(): void
    {
        $token = (string) \Illuminate\Support\Str::uuid();

        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'verification_token' => $token,
        ]);

        $this->assertEquals($token, $cert->verification_token);
    }

    #[Test]
    public function each_certificate_gets_a_unique_verification_token(): void
    {
        $certs = Certificate::factory()->forInstitution($this->institution)->count(5)->create([
            'verification_token' => null,
        ]);

        $this->assertCount(5, $certs->pluck('verification_token')->unique());
    }

    // ══════════════════════════════════════════════
    // QR Code — auto generate saat created()
    // ══════════════════════════════════════════════

    #[Test]
    public function qr_code_is_auto_generated_on_certificate_creation(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'qr_code' => null,
        ]);

        $cert->refresh();

        $this->assertNotEmpty($cert->qr_code);
        $this->assertStringStartsWith('data:image/png;base64,', $cert->qr_code);
    }

    #[Test]
    public function get_qr_code_data_uri_generates_on_demand_when_empty(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        // Paksa kosongkan qr_code langsung di DB (tanpa event) untuk simulasi belum ada
        Certificate::withoutEvents(fn () => $cert->update(['qr_code' => null]));
        $cert->refresh();
        $this->assertEmpty($cert->qr_code);

        $dataUri = $cert->getQrCodeDataUri();

        $this->assertNotEmpty($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
    }

    #[Test]
    public function get_qr_code_data_uri_returns_existing_value_without_regenerating(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $existing = $cert->qr_code;

        $this->assertEquals($existing, $cert->getQrCodeDataUri());
    }

    // ══════════════════════════════════════════════
    // verificationUrl()
    // ══════════════════════════════════════════════

    #[Test]
    public function verification_url_contains_token(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertStringContainsString($cert->verification_token, $cert->verificationUrl());
    }

    #[Test]
    public function verification_url_contains_verify_path(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertStringContainsString('/verify/', $cert->verificationUrl());
    }

    // ══════════════════════════════════════════════
    // participantUrl()
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
    // pdfUrl()
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

    #[Test]
    public function all_three_public_urls_share_the_same_token_but_different_paths(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->assertNotEquals($cert->verificationUrl(), $cert->participantUrl());
        $this->assertNotEquals($cert->participantUrl(), $cert->pdfUrl());
        $this->assertStringContainsString($cert->verification_token, $cert->verificationUrl());
        $this->assertStringContainsString($cert->verification_token, $cert->participantUrl());
        $this->assertStringContainsString($cert->verification_token, $cert->pdfUrl());
    }
}

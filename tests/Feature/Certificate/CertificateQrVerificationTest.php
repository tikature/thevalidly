<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: QR Code Verification — Iterasi 4
 *
 * Menguji:
 *  1. QR code ter-generate otomatis saat sertifikat dibuat
 *  2. Halaman verifikasi publik (web) bekerja dengan benar
 *  3. API endpoint JSON untuk QR scanner bekerja dengan benar
 *  4. Token invalid ditangani dengan tepat
 *
 * Jalankan: php artisan test --filter CertificateQrVerificationTest
 */
class CertificateQrVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['name' => 'Lembaga Test CEH']);

        $this->certificate = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Budi Santoso',
            'perusahaan'     => 'PT. Maju Bersama',
            'nomor'          => 'CERT/001/2026',
            'event_name'     => 'Pelatihan Laravel Iterasi 4',
            'date_start'     => '2026-05-10',
            'date_end'       => null,
            'signer_name'    => 'Dr. Ahmad',
            'signer_title'   => 'Direktur Lembaga',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  1. QR CODE GENERATION
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function qr_code_is_generated_automatically_on_certificate_creation(): void
    {
        // Certificate di-create di setUp — qr_code harus sudah terisi
        $this->assertNotNull($this->certificate->fresh()->qr_code);
    }

    #[Test]
    public function qr_code_is_a_valid_png_data_uri(): void
    {
        $qr = $this->certificate->fresh()->qr_code;

        // Format: data:image/png;base64,... — PNG via GD, aman di DomPDF
        $this->assertStringStartsWith('data:image/png;base64,', $qr);

        $base64  = substr($qr, strlen('data:image/png;base64,'));
        $decoded = base64_decode($base64, strict: true);
        $this->assertNotFalse($decoded, 'QR code bukan base64 yang valid.');
        // PNG magic bytes: \x89PNG
        $this->assertStringStartsWith("\x89PNG", $decoded, 'Data bukan PNG yang valid.');
    }

    #[Test]
    public function qr_code_url_points_to_verification_route(): void
    {
        // URL verifikasi harus mengandung verification_token
        $expectedUrl = url('/verify/' . $this->certificate->verification_token);
        $this->assertSame($expectedUrl, $this->certificate->verificationUrl());
    }

    #[Test]
    public function get_qr_code_data_uri_generates_on_demand_if_missing(): void
    {
        // Paksa qr_code kosong tanpa trigger event
        Certificate::withoutEvents(function () {
            $this->certificate->update(['qr_code' => null]);
        });

        $this->assertNull($this->certificate->fresh()->qr_code);

        // Panggil helper — harus generate dan simpan sebagai PNG data URI
        $uri = $this->certificate->fresh()->getQrCodeDataUri();

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $this->assertNotNull(Certificate::find($this->certificate->id)->qr_code);
    }

    // ══════════════════════════════════════════════════════════════
    //  2. HALAMAN VERIFIKASI WEB (publik)
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function verification_page_is_publicly_accessible(): void
    {
        $this->get(route('certificate.verify', $this->certificate->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function valid_token_shows_certificate_details_on_web(): void
    {
        $this->get(route('certificate.verify', $this->certificate->verification_token))
            ->assertStatus(200)
            ->assertSee('Budi Santoso')
            ->assertSee('CERT/001/2026')
            ->assertSee('Pelatihan Laravel Iterasi 4')
            ->assertSee('Lembaga Test CEH');
    }

    #[Test]
    public function verification_page_shows_valid_badge(): void
    {
        $this->get(route('certificate.verify', $this->certificate->verification_token))
            ->assertSee('Sertifikat Valid');
    }

    #[Test]
    public function invalid_token_on_web_shows_verify_invalid_view(): void
    {
        $this->get(route('certificate.verify', 'token-tidak-ada'))
            ->assertStatus(200)             // view verify-invalid di-return, bukan 404
            ->assertViewIs('certificate.verify-invalid');
    }

    // ══════════════════════════════════════════════════════════════
    //  3. API ENDPOINT JSON (untuk QR scanner)
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function api_verify_returns_200_and_valid_true_for_existing_token(): void
    {
        $this->getJson(route('certificate.verify.api', $this->certificate->verification_token))
            ->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    #[Test]
    public function api_verify_returns_correct_certificate_structure(): void
    {
        $this->getJson(route('certificate.verify.api', $this->certificate->verification_token))
            ->assertStatus(200)
            ->assertJsonStructure([
                'valid',
                'certificate' => [
                    'nama',
                    'perusahaan',
                    'nomor',
                    'event_name',
                    'date_start',
                    'date_end',
                    'event_place',
                    'institution',
                    'issued_at',
                    'verification_url',
                ],
            ]);
    }

    #[Test]
    public function api_verify_returns_correct_certificate_data(): void
    {
        $this->getJson(route('certificate.verify.api', $this->certificate->verification_token))
            ->assertStatus(200)
            ->assertJsonPath('certificate.nama', 'Budi Santoso')
            ->assertJsonPath('certificate.nomor', 'CERT/001/2026')
            ->assertJsonPath('certificate.event_name', 'Pelatihan Laravel Iterasi 4')
            ->assertJsonPath('certificate.institution', 'Lembaga Test CEH');
    }

    #[Test]
    public function api_verify_verification_url_is_correct(): void
    {
        $expected = url('/verify/' . $this->certificate->verification_token);

        $this->getJson(route('certificate.verify.api', $this->certificate->verification_token))
            ->assertJsonPath('certificate.verification_url', $expected);
    }

    #[Test]
    public function api_verify_returns_404_for_invalid_token(): void
    {
        $this->getJson(route('certificate.verify.api', 'token-palsu-tidak-ada'))
            ->assertStatus(404)
            ->assertJson([
                'valid'   => false,
                'message' => 'Sertifikat tidak ditemukan.',
            ]);
    }

    #[Test]
    public function api_verify_returns_404_for_valid_uuid_format_but_nonexistent(): void
    {
        $fakeUuid = '00000000-0000-4000-a000-000000000000';

        $this->getJson(route('certificate.verify.api', $fakeUuid))
            ->assertStatus(404)
            ->assertJson(['valid' => false]);
    }

    #[Test]
    public function api_verify_works_without_perusahaan(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'perusahaan'     => null,
        ]);

        $this->getJson(route('certificate.verify.api', $cert->verification_token))
            ->assertStatus(200)
            ->assertJson(['valid' => true])
            ->assertJsonPath('certificate.perusahaan', null);
    }

    // ══════════════════════════════════════════════════════════════
    //  4. ROUTE SANITY
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function api_verify_route_is_publicly_accessible_without_auth(): void
    {
        // Pastikan tidak ada middleware auth yang memblokir
        $this->getJson(route('certificate.verify.api', $this->certificate->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function api_verify_route_name_is_correct(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('certificate.verify.api'),
            'Route certificate.verify.api tidak terdaftar.'
        );
    }
}

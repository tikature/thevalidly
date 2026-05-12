<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Landing Page — Verifikasi Sertifikat (Iterasi 4)
 *
 * Jalankan: php artisan test --filter LandingVerificationTest
 */
class LandingVerificationTest extends TestCase
{
    use RefreshDatabase;

    // ── Landing Page ─────────────────────────────────────────

    #[Test]
    public function landing_page_is_accessible(): void
    {
        $this->get(route('landing'))
            ->assertStatus(200);
    }

    #[Test]
    public function landing_page_shows_verification_section(): void
    {
        $this->get(route('landing'))
            ->assertStatus(200)
            ->assertSee('Verifikasi Sertifikat')
            ->assertSee('verifikasi', false); // id="verifikasi" di section
    }

    #[Test]
    public function landing_page_shows_verify_input(): void
    {
        $this->get(route('landing'))
            ->assertSee('verifyToken', false)
            ->assertSee('Masukkan kode verifikasi');
    }

    #[Test]
    public function landing_page_shows_upload_qr_option(): void
    {
        $this->get(route('landing'))
            ->assertSee('uploadQrInput', false)
            ->assertSee('Upload foto atau PDF sertifikat');
    }

    #[Test]
    public function landing_page_loads_jsqr_library(): void
    {
        $this->get(route('landing'))
            ->assertSee('jsqr', false);
    }

    // ── Verifikasi via Token ─────────────────────────────────

    #[Test]
    public function verify_route_redirects_correctly_for_valid_token(): void
    {
        $institution = Institution::factory()->create();
        $cert = Certificate::factory()->create([
            'institution_id' => $institution->id,
        ]);

        // Simulasi user klik Verifikasi dari landing page
        $this->get('/verify/' . $cert->verification_token)
            ->assertStatus(200);
    }

    #[Test]
    public function verify_route_shows_certificate_data_for_valid_token(): void
    {
        $institution = Institution::factory()->create(['name' => 'Lembaga Uji']);
        $cert = Certificate::factory()->create([
            'institution_id' => $institution->id,
            'nama'           => 'Budi Santoso',
            'event_name'     => 'Pelatihan Verifikasi',
        ]);

        $this->get('/verify/' . $cert->verification_token)
            ->assertStatus(200)
            ->assertSee('Budi Santoso')
            ->assertSee('Pelatihan Verifikasi');
    }

    #[Test]
    public function verify_route_shows_invalid_view_for_unknown_token(): void
    {
        $this->get('/verify/token-tidak-dikenal')
            ->assertStatus(200)
            ->assertViewIs('certificate.verify-invalid');
    }

    #[Test]
    public function verify_route_is_publicly_accessible_without_login(): void
    {
        $institution = Institution::factory()->create();
        $cert = Certificate::factory()->create([
            'institution_id' => $institution->id,
        ]);

        // Tanpa auth — harus tetap bisa akses
        $this->get('/verify/' . $cert->verification_token)
            ->assertStatus(200);
    }

    // ── API Verifikasi (untuk QR scanner) ───────────────────

    #[Test]
    public function api_verify_returns_valid_json_for_valid_token(): void
    {
        $institution = Institution::factory()->create(['name' => 'Lembaga API']);
        $cert = Certificate::factory()->create([
            'institution_id' => $institution->id,
            'nama'           => 'Citra Lestari',
        ]);

        $this->getJson('/api/verify/' . $cert->verification_token)
            ->assertStatus(200)
            ->assertJson(['valid' => true])
            ->assertJsonPath('certificate.nama', 'Citra Lestari')
            ->assertJsonPath('certificate.institution', 'Lembaga API');
    }

    #[Test]
    public function api_verify_returns_404_for_invalid_token(): void
    {
        $this->getJson('/api/verify/token-tidak-ada')
            ->assertStatus(404)
            ->assertJson(['valid' => false]);
    }


    // ── Fitur PDF & UI tambahan ──────────────────────────────

    #[Test]
    public function landing_page_shows_pdf_upload_support(): void
    {
        $this->get(route('landing'))
            ->assertSee('application/pdf', false)
            ->assertSee('PDF sertifikat');
    }

    #[Test]
    public function landing_page_loads_pdfjs_library(): void
    {
        $this->get(route('landing'))
            ->assertSee('pdfjs-dist', false);
    }

    #[Test]
    public function landing_page_has_hero_with_verify_shortcut(): void
    {
        $this->get(route('landing'))
            ->assertSee('#verifikasi', false)
            ->assertSee('Verifikasi Sertifikat');
    }
}

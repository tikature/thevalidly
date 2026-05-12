<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Certificate Show Page — LinkedIn Share
 *
 * Menguji:
 *  1. Halaman show sertifikat bisa diakses publik
 *  2. Data sertifikat tampil dengan format Str::title()
 *  3. Field nullable (signer_name, event_place, perusahaan) aman saat null
 *  4. Tombol LinkedIn ada di halaman
 *  5. Data LinkedIn (CERT JS object) ter-render dengan benar
 *
 * Jalankan: php artisan test --filter CertificateShowLinkedInTest
 */
class CertificateShowLinkedInTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Lembaga Pelatihan Nasional',
        ]);

        $this->certificate = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'budi santoso',      // sengaja lowercase — harus di-title
            'perusahaan'     => 'pt. maju bersama',
            'nomor'          => 'CERT/001/2026',
            'event_name'     => 'pelatihan keselamatan kerja',
            'event_date'     => '10 Mei 2026',
            'event_place'    => 'jakarta selatan',
            'signer_name'    => 'dr. ahmad fauzi',
            'signer_title'   => 'direktur lembaga',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  1. AKSES HALAMAN
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function certificate_show_page_is_publicly_accessible(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function certificate_show_returns_404_for_invalid_token(): void
    {
        $this->get(route('certificate.participant', 'token-tidak-ada'))
            ->assertStatus(404);
    }

    // ══════════════════════════════════════════════════════════════
    //  2. FORMAT Str::title()
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function nama_is_displayed_with_title_case(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Budi Santoso')
            ->assertDontSee('budi santoso');
    }

    #[Test]
    public function event_name_is_displayed_with_title_case(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Pelatihan Keselamatan Kerja')
            ->assertDontSee('pelatihan keselamatan kerja');
    }

    #[Test]
    public function signer_name_is_displayed_with_title_case(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Dr. Ahmad Fauzi')
            ->assertDontSee('dr. ahmad fauzi');
    }

    #[Test]
    public function signer_title_is_displayed_with_title_case(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Direktur Lembaga')
            ->assertDontSee('direktur lembaga');
    }

    #[Test]
    public function perusahaan_is_displayed_with_title_case(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Pt. Maju Bersama');
    }

    #[Test]
    public function event_place_is_displayed_with_title_case(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Jakarta Selatan')
            ->assertDontSee('jakarta selatan');
    }

    // ══════════════════════════════════════════════════════════════
    //  3. FIELD NULLABLE — tidak boleh error saat null
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function page_renders_without_error_when_perusahaan_is_null(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'perusahaan'     => null,
        ]);

        $this->get(route('certificate.participant', $cert->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function page_renders_without_error_when_signer_name_is_null(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'signer_name'    => null,
            'signer_title'   => null,
        ]);

        $this->get(route('certificate.participant', $cert->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function page_renders_without_error_when_event_place_is_null(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'event_place'    => null,
        ]);

        $this->get(route('certificate.participant', $cert->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function signer_section_is_hidden_when_signer_name_is_null(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'signer_name'    => null,
            'signer_title'   => null,
        ]);

        $this->get(route('certificate.participant', $cert->verification_token))
            ->assertDontSee('Ditandatangani Oleh');
    }

    #[Test]
    public function event_place_section_is_hidden_when_null(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'event_place'    => null,
        ]);

        $this->get(route('certificate.participant', $cert->verification_token))
            ->assertDontSee('Tempat Pelaksanaan');
    }

    // ══════════════════════════════════════════════════════════════
    //  4. TOMBOL LINKEDIN
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function linkedin_button_is_present_on_page(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Tambah ke LinkedIn');
    }

    #[Test]
    public function linkedin_modal_markup_is_present(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('liModalBackdrop', false)
            ->assertSee('Tambah ke Profil LinkedIn');
    }

    // ══════════════════════════════════════════════════════════════
    //  5. DATA LINKEDIN (JS CERT object)
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function linkedin_cert_object_contains_event_name(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Pelatihan Keselamatan Kerja');
    }

    #[Test]
    public function linkedin_cert_object_contains_institution_name(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Lembaga Pelatihan Nasional');
    }

    #[Test]
    public function linkedin_cert_object_contains_nomor(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('CERT/001/2026');
    }

    #[Test]
    public function linkedin_cert_object_contains_verification_url(): void
    {
        $expectedUrl = $this->certificate->verificationUrl();

        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee($expectedUrl, false);
    }

    #[Test]
    public function linkedin_cert_object_contains_signer_when_present(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('Dr. Ahmad Fauzi')
            ->assertSee('Direktur Lembaga');
    }

    // ══════════════════════════════════════════════════════════════
    //  6. EVENT DATE DI DESKRIPSI LINKEDIN
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function linkedin_description_contains_event_date(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('10 Mei 2026');
    }

    #[Test]
    public function linkedin_description_contains_event_date_and_place_when_both_present(): void
    {
        $this->get(route('certificate.participant', $this->certificate->verification_token))
            ->assertSee('10 Mei 2026')
            ->assertSee('Jakarta Selatan');
    }
}
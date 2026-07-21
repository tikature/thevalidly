<?php

namespace Tests\Feature\Iterasi4;

use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-022 — Verifikasi Mandiri di Landing
 * Iterasi 4 | US22
 *
 * Catatan: form verifikasi di landing menggunakan JavaScript (doVerify())
 * yang redirect ke /verify/{token} via window.location.href — bukan form POST.
 * Test memvalidasi behavior server dari URL yang dihasilkan JavaScript tersebut.
 *
 * Jumlah test method: 3 (sesuai jumlah AC)
 */
class VerifikasiMandiriDiLandingTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['is_active' => true]);
    }

    /**
     * AC1: Halaman utama Validly menyediakan form input token yang dapat
     * digunakan siapapun untuk memverifikasi sertifikat tanpa perlu login.
     */
    public function test_halaman_landing_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertViewIs('landing');
    }

    /**
     * AC2: Ketika token yang dimasukkan valid dan form disubmit, pengguna
     * diarahkan ke halaman verifikasi yang sesuai.
     *
     * Catatan: submit dilakukan via JavaScript redirect ke /verify/{token}.
     * Test memvalidasi bahwa URL tujuan redirect menghasilkan halaman verifikasi
     * yang benar.
     */
    public function test_token_valid_menghasilkan_halaman_verifikasi_yang_benar(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Budi Santoso',
            'event_name'     => 'Pelatihan K3',
        ]);

        $response = $this->get('/verify/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewIs('certificate.verify');
        $response->assertViewHas('certificate', function ($c) use ($cert) {
            return $c->id === $cert->id;
        });
    }

    /**
     * AC3: Ketika token yang dimasukkan tidak valid (tidak ditemukan di sistem),
     * sistem tidak melakukan redirect ke halaman verifikasi dan menampilkan
     * pesan token tidak ditemukan.
     */
    public function test_token_invalid_tidak_diarahkan_ke_halaman_verifikasi(): void
    {
        $response = $this->get('/verify/token-invalid-tidak-terdaftar');

        $response->assertOk();
        $response->assertViewIs('certificate.verify-invalid');
        $response->assertViewHas('token', 'token-invalid-tidak-terdaftar');
    }
}
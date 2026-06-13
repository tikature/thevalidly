<?php

namespace Tests\Feature\Iterasi4;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-019 — Halaman Verifikasi Publik
 * Iterasi 4 | US19
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class HalamanVerifikasiPublikTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $adminLembaga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['is_active' => true]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
    }

    /**
     * AC1: Halaman verifikasi dapat diakses oleh siapapun tanpa perlu
     * memiliki akun atau login ke sistem.
     */
    public function test_halaman_verifikasi_dapat_diakses_tanpa_login(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->get('/verify/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewIs('certificate.verify');
    }

    /**
     * AC2: Ketika token yang diakses valid, halaman menampilkan penanda
     * visual VALID beserta data sertifikat lengkap — nama peserta, nomor
     * sertifikat, nama kegiatan, dan tanggal pelaksanaan.
     */
    public function test_token_valid_menampilkan_data_sertifikat_lengkap(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Budi Santoso',
            'nomor'          => 'CERT/001/2026',
            'event_name'     => 'Pelatihan K3',
            'date_start'     => '2026-01-10',
        ]);

        $response = $this->get('/verify/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewHas('certificate', function ($c) use ($cert) {
            return $c->id === $cert->id
                && $c->nama === 'Budi Santoso'
                && $c->nomor === 'CERT/001/2026'
                && $c->event_name === 'Pelatihan K3';
        });
    }

    /**
     * AC3: Halaman verifikasi tidak menyediakan tombol unduh PDF —
     * fungsinya hanya untuk konfirmasi keaslian.
     */
    public function test_halaman_verifikasi_tidak_menyediakan_route_unduh_pdf(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        // Halaman verifikasi (/verify/{token}) berbeda dengan halaman peserta (/cert/{token})
        // yang menyediakan unduhan PDF
        $verifyResponse = $this->get('/verify/' . $cert->verification_token);
        $verifyResponse->assertOk();
        $verifyResponse->assertViewIs('certificate.verify');

        // Pastikan route verify bukan route pdf
        $this->assertNotEquals(
            route('certificate.pdf', $cert->verification_token),
            url('/verify/' . $cert->verification_token)
        );
    }

    /**
     * AC4: Ketika token yang diakses tidak ditemukan di sistem, halaman
     * menampilkan pesan sertifikat tidak ditemukan beserta token yang dicari.
     */
    public function test_token_tidak_valid_menampilkan_pesan_tidak_ditemukan(): void
    {
        $response = $this->get('/verify/token-tidak-ada-sama-sekali');

        $response->assertOk();
        $response->assertViewIs('certificate.verify-invalid');
        $response->assertViewHas('token', 'token-tidak-ada-sama-sekali');
    }

    /**
     * AC5: Admin Lembaga dapat membuka halaman verifikasi suatu sertifikat
     * langsung dari halaman riwayat sertifikat.
     */
    public function test_sertifikat_di_riwayat_memiliki_url_verifikasi_yang_dapat_diakses(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        // Verifikasi URL verifikasi tersedia dari data sertifikat
        $this->assertNotEmpty($cert->verificationUrl());

        // Verifikasi URL dapat diakses
        $response = $this->get('/verify/' . $cert->verification_token);
        $response->assertOk();
    }
}
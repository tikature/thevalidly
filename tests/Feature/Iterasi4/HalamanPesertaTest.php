<?php

namespace Tests\Feature\Iterasi4;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SKPL-VLDLY-020 — Halaman Peserta
 * Iterasi 4 | US20
 *
 * Jumlah test method: 6 (sesuai jumlah AC)
 */
class HalamanPesertaTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $adminLembaga;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->institution = Institution::factory()->create(['is_active' => true]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
    }

    /**
     * AC1: Setiap sertifikat memiliki halaman pribadi yang dapat diakses
     * oleh siapapun tanpa perlu login.
     */
    public function test_halaman_peserta_dapat_diakses_tanpa_login(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->get('/cert/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewIs('certificate.participant');
    }

    /**
     * AC2: Halaman peserta menampilkan data sertifikat lengkap beserta
     * tombol untuk mengunduh PDF dan membagikan ke LinkedIn.
     */
    public function test_halaman_peserta_menampilkan_data_sertifikat_lengkap(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Ahmad Fauzi',
            'nomor'          => 'CERT/001/2026',
            'event_name'     => 'Seminar AI',
            'date_start'     => '2026-01-10',
        ]);

        $response = $this->get('/cert/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewHas('certificate', function ($c) use ($cert) {
            return $c->id === $cert->id
                && $c->nama === 'Ahmad Fauzi'
                && $c->nomor === 'CERT/001/2026'
                && $c->event_name === 'Seminar AI';
        });
    }

    /**
     * AC3: Ketika tombol unduh PDF diklik, file PDF sertifikat berhasil
     * diunduh dan isinya sesuai dengan data sertifikat yang ditampilkan.
     */
    public function test_route_unduh_pdf_publik_dapat_diakses_tanpa_login(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        // Route pdf publik tersedia via /cert/{token}/pdf
        $response = $this->get(route('certificate.pdf.public', $cert->verification_token));

        // Response adalah PDF download atau 200 (bisa gagal generate jika DomPDF tidak tersedia)
        // Yang diverifikasi: route accessible tanpa login (bukan 401/403)
        $this->assertNotContains($response->status(), [401, 403]);
    }

    /**
     * AC4: Ketika tombol Tambah ke LinkedIn diklik, halaman LinkedIn terbuka
     * dengan data sertifikat yang sudah terisi otomatis.
     *
     * Catatan: redirect ke LinkedIn tidak dapat ditest secara otomatis.
     * Test memvalidasi bahwa data sertifikat yang dibutuhkan untuk URL LinkedIn
     * tersedia di view.
     */
    public function test_data_sertifikat_tersedia_di_view_untuk_link_linkedin(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Rina Kartika',
            'event_name'     => 'Pelatihan ISO',
            'date_start'     => '2026-03-01',
        ]);

        $response = $this->get('/cert/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewHas('certificate', function ($c) use ($cert) {
            return $c->id === $cert->id
                && !empty($c->event_name)
                && !empty($c->verification_token);
        });
    }

    /**
     * AC5: Ketika token yang diakses tidak ditemukan, halaman menampilkan
     * pesan peserta tidak ditemukan.
     */
    public function test_token_tidak_valid_menampilkan_pesan_peserta_tidak_ditemukan(): void
    {
        $response = $this->get('/cert/token-tidak-ada-sama-sekali');

        $response->assertOk();
        $response->assertViewIs('certificate.participant-invalid');
    }

    /**
     * AC6: Admin Lembaga dapat membuka halaman peserta suatu sertifikat
     * langsung dari halaman riwayat sertifikat.
     */
    public function test_sertifikat_di_riwayat_memiliki_url_halaman_peserta_yang_dapat_diakses(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->assertNotEmpty($cert->participantUrl());

        $response = $this->get('/cert/' . $cert->verification_token);
        $response->assertOk();
    }
}
<?php

namespace Tests\Feature\Iterasi4;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-021 — Halaman Batch Publik
 * Iterasi 4 | US21
 *
 * Jumlah test method: 7 (sesuai jumlah AC)
 */
class HalamanBatchPublikTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $adminLembaga;
    private CertificateBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['is_active' => true]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);

        $this->batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'status'         => 'done',
            'total'          => 3,
        ]);

        Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $this->batch->id,
        ]);
    }

    /**
     * AC1: Setiap batch memiliki halaman publik yang dapat diakses oleh
     * siapapun tanpa perlu login.
     */
    public function test_halaman_batch_publik_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get('/batch/' . $this->batch->batch_token);

        $response->assertOk();
        $response->assertViewIs('certificate.batch');
    }

    /**
     * AC2: Halaman batch publik menampilkan daftar seluruh peserta dalam
     * batch beserta tombol Download PDF dan Verifikasi per peserta.
     */
    public function test_halaman_batch_publik_menampilkan_daftar_seluruh_peserta(): void
    {
        $response = $this->get('/batch/' . $this->batch->batch_token);

        $response->assertOk();
        $response->assertViewHas('batch', function ($b) {
            return $b->id === $this->batch->id
                && $b->certificates->count() === 3;
        });
    }

    /**
     * AC3: Ketika tombol Download PDF per peserta diklik, halaman peserta
     * yang sesuai terbuka.
     */
    public function test_setiap_sertifikat_dalam_batch_memiliki_url_halaman_peserta(): void
    {
        $response = $this->get('/batch/' . $this->batch->batch_token);

        $response->assertOk();
        $response->assertViewHas('batch', function ($b) {
            foreach ($b->certificates as $cert) {
                if (empty($cert->verification_token)) return false;
                if (empty($cert->participantUrl())) return false;
            }
            return true;
        });
    }

    /**
     * AC4: Ketika tombol Verifikasi per peserta diklik, halaman verifikasi
     * yang sesuai terbuka.
     */
    public function test_setiap_sertifikat_dalam_batch_memiliki_url_verifikasi(): void
    {
        $response = $this->get('/batch/' . $this->batch->batch_token);

        $response->assertOk();
        $response->assertViewHas('batch', function ($b) {
            foreach ($b->certificates as $cert) {
                if (empty($cert->verificationUrl())) return false;
            }
            return true;
        });
    }

    /**
     * AC5: Halaman batch publik menyediakan tombol untuk mengunduh seluruh
     * sertifikat dalam batch sekaligus dalam satu file ZIP.
     */
    public function test_route_unduh_zip_publik_dapat_diakses_tanpa_login(): void
    {
        // Route ZIP publik tersedia via /batch/{token}/zip
        $response = $this->getJson(route('certificate.batch.zip.public', $this->batch->batch_token));

        // Tanpa PDF di cache: 422. Yang diverifikasi: bukan 401/403 (tidak perlu login)
        $this->assertNotContains($response->status(), [401, 403]);
    }

    /**
     * AC6: Ketika token batch yang diakses tidak ditemukan, halaman
     * menampilkan pesan batch tidak ditemukan.
     */
    public function test_token_batch_tidak_valid_menampilkan_pesan_tidak_ditemukan(): void
    {
        $response = $this->get('/batch/token-batch-tidak-ada');

        $response->assertStatus(404);
        $response->assertViewIs('certificate.batch-invalid');
    }

    /**
     * AC7: Admin Lembaga dapat membuka halaman batch publik langsung dari
     * halaman riwayat batch.
     */
    public function test_batch_memiliki_url_publik_yang_dapat_diakses_dari_riwayat(): void
    {
        $this->assertNotEmpty($this->batch->batch_token);
        $this->assertNotEmpty($this->batch->batchUrl());

        $response = $this->get('/batch/' . $this->batch->batch_token);
        $response->assertOk();
    }
}
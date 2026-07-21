<?php

namespace Tests\Feature\Iterasi4;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-018 — QR Code pada Sertifikat
 * Iterasi 4 | US18
 *
 * Jumlah test method: 3 (sesuai jumlah AC)
 */
class QrCodePadaSertifikatTest extends TestCase
{
    use RefreshDatabase;

    private User $adminLembaga;
    private Institution $institution;

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
     * AC1: Setiap sertifikat PDF yang diterbitkan menyertakan QR Code
     * yang tertanam di dalamnya.
     */
    public function test_setiap_sertifikat_yang_diterbitkan_memiliki_qr_code(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Pelatihan K3',
                'date_start' => '2026-01-10',
            ]);

        $response->assertOk();

        $token = $response->json('verification_token');
        $cert  = Certificate::where('verification_token', $token)->first();

        $this->assertNotNull($cert->qr_code);
        $this->assertStringStartsWith('data:image/png;base64,', $cert->qr_code);
    }

    /**
     * AC2: QR Code yang tertanam mengarah ke halaman verifikasi publik
     * sertifikat yang bersangkutan.
     */
    public function test_qr_code_mengarah_ke_url_verifikasi_yang_benar(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.store'), [
                'nama'       => 'Siti Rahayu',
                'nomor'      => 'CERT/002/2026',
                'event_name' => 'Workshop Python',
                'date_start' => '2026-02-01',
            ]);

        $response->assertOk();

        $token = $response->json('verification_token');
        $cert  = Certificate::where('verification_token', $token)->first();

        $expectedUrl = url('/verify/' . $cert->verification_token);
        $this->assertEquals($expectedUrl, $cert->verificationUrl());
    }

    /**
     * AC3: QR Code dapat dipindai menggunakan smartphone dan membuka
     * halaman verifikasi yang benar.
     *
     * Catatan: pemindaian fisik tidak dapat ditest secara otomatis.
     * Test memvalidasi bahwa URL yang dikodekan ke QR Code dapat diakses
     * dan menghasilkan halaman verifikasi yang valid.
     */
    public function test_url_yang_dikodekan_qr_code_dapat_diakses_dan_menampilkan_verifikasi(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->get('/verify/' . $cert->verification_token);

        $response->assertOk();
        $response->assertViewIs('certificate.verify');
    }
}
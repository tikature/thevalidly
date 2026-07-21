<?php

namespace Tests\Unit\Iterasi4;

use Tests\TestCase;
use App\Http\Controllers\CertificateVerificationController;
use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;

class CertificateVerificationControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_verify_returns_success_json_when_certificate_found()
    {
        // 1. Buat Lembaga & Sertifikat dummy di DB
        $institution = Institution::factory()->create(['name' => 'Lembaga Validly']);
        $certificate = Certificate::factory()->create([
            'institution_id'     => $institution->id,
            'verification_token' => 'TOKEN-TEST-VALID-123',
            'nama'               => 'Budi Santoso',
            'perusahaan'         => 'PT Validly Indonesia',
            'nomor'              => 'CERT/2026/001',
            'event_name'         => 'Webinar Laravel Unit Test',
            'date_start'         => '2026-07-01',
            'date_end'           => '2026-07-02',
            'event_place'        => 'Online Zoom',
            'issued_at'          => now(),
        ]);

        $controller = new CertificateVerificationController();

        // 2. Panggil method apiVerify secara langsung
        /** @var JsonResponse $response */
        $response = $controller->apiVerify('TOKEN-TEST-VALID-123');

        // 3. Assert status code & isi JSON response
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['valid']);
        $this->assertEquals('Budi Santoso', $data['certificate']['nama']);
        $this->assertEquals('Lembaga Validly', $data['certificate']['institution']);
        $this->assertEquals('CERT/2026/001', $data['certificate']['nomor']);
        $this->assertArrayHasKey('verification_url', $data['certificate']);
    }

    public function test_api_verify_returns_404_json_when_certificate_not_found()
    {
        $controller = new CertificateVerificationController();

        // Panggil dengan token yang tidak ada di DB
        /** @var JsonResponse $response */
        $response = $controller->apiVerify('TOKEN-INVALID-999');

        $this->assertEquals(404, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['valid']);
        $this->assertEquals('Sertifikat tidak ditemukan.', $data['message']);
    }
}
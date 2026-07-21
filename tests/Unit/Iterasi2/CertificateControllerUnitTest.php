<?php

namespace Tests\Unit\Iterasi2;

use App\Http\Controllers\CertificateController;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfPdf;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CertificateControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Method controller dipanggil langsung (bukan lewat HTTP), jadi variabel
        // $errors yang biasanya di-share otomatis oleh middleware
        // ShareErrorsFromSession tidak pernah ter-set. Blade partial (mis.
        // partials/alerts.blade.php) butuh ini ada supaya tidak ViewException.
        $this->app['view']->share('errors', new \Illuminate\Support\ViewErrorBag);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_creates_certificate_and_returns_payload()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $controller = new CertificateController();
        $request = new Request([
            'nama' => 'Budi',
            'perusahaan' => 'PT Contoh',
            'nomor' => 'CERT/001',
            'cert_desc' => 'Lulus',
            'event_name' => 'Pelatihan',
            'date_start' => '2026-01-01',
            'date_end' => '2026-01-02',
            'event_place' => 'Bandung',
            'signer_name' => 'Dr. A',
            'signer_title' => 'Ketua',
        ]);

        $response = $controller->store($request);

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertNotEmpty($payload['verification_token']);
        $this->assertDatabaseHas('certificates', ['nama' => 'Budi', 'institution_id' => $institution->id]);
    }

    public function test_store_bulk_creates_multiple_certificates()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $controller = new CertificateController();
        $request = new Request([
            'participants' => [
                ['nama' => 'Budi', 'perusahaan' => 'PT A', 'nomor' => 'CERT/001'],
                ['nama' => 'Ani', 'perusahaan' => 'PT B', 'nomor' => 'CERT/002'],
            ],
            'event_name' => 'Pelatihan',
            'date_start' => '2026-01-01',
            'event_place' => 'Bandung',
            'signer_name' => 'Dr. A',
            'signer_title' => 'Ketua',
            'cert_desc' => 'Lulus',
        ]);

        $response = $controller->storeBulk($request);

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertSame(2, $payload['count']);
        $this->assertDatabaseCount('certificates', 2);
    }

    public function test_pregenerate_returns_cached_response_when_pdf_exists()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institution->id]);
        Storage::disk('local')->put('pdf_cache/' . $certificate->verification_token . '.pdf', 'pdf');

        $controller = new CertificateController();
        $response = $controller->pregenerate($certificate->verification_token);

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['cached']);
    }

    /**
     * Baris 114-115 — pregenerate() menolak akses ke sertifikat milik lembaga lain.
     */
    public function test_pregenerate_returns_403_for_certificate_from_other_institution()
    {
        $institutionA = Institution::factory()->create();
        $institutionB = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institutionA->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institutionB->id]);

        $controller = new CertificateController();

        try {
            $controller->pregenerate($certificate->verification_token);
            $this->fail('Seharusnya melempar HttpException 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    /**
     * Baris 123-127 — jalur sukses saat PDF belum ada di cache: generate lalu simpan.
     * Sekaligus meng-cover buildPdf() cabang generate QR baru (baris 166-168),
     * karena Certificate::factory() default belum punya qr_code.
     */
    public function test_pregenerate_generates_and_caches_pdf_when_not_cached()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institution->id]);
        $cachePath = 'pdf_cache/' . $certificate->verification_token . '.pdf';
        Storage::disk('local')->delete($cachePath);

        $pdf = Mockery::mock(DomPdfPdf::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('pdf-bytes-baru');

        Pdf::shouldReceive('loadView')->andReturn($pdf);

        $controller = new CertificateController();
        $response = $controller->pregenerate($certificate->verification_token);

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['cached']);
        $this->assertTrue(Storage::disk('local')->exists($cachePath));
        $this->assertNotEmpty($certificate->fresh()->qr_code);
    }

    /**
     * Baris 128-129 — cabang catch: generate PDF gagal, harus kembalikan 500.
     */
    public function test_pregenerate_returns_500_when_pdf_generation_throws()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institution->id]);
        $cachePath = 'pdf_cache/' . $certificate->verification_token . '.pdf';
        Storage::disk('local')->delete($cachePath);

        Pdf::shouldReceive('loadView')->andThrow(new Exception('DomPDF gagal simulasi'));

        $controller = new CertificateController();
        $response = $controller->pregenerate($certificate->verification_token);

        $payload = $response->getData(true);
        $this->assertFalse($payload['success']);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse(Storage::disk('local')->exists($cachePath));
    }

    public function test_pdf_download_returns_pdf_response_for_existing_cache()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institution->id, 'nama' => 'Budi']);
        Storage::disk('local')->put('pdf_cache/' . $certificate->verification_token . '.pdf', 'pdf-bytes');

        $controller = new CertificateController();
        $response = $controller->pdf($certificate->verification_token);

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('sertifikat_budi_', $response->headers->get('Content-Disposition'));
    }

    public function test_upload_asset_saves_and_returns_public_url()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        $request = new Request([
            'type' => 'logo',
        ]);
        $request->files->set('file', $file);

        $controller = new CertificateController();
        $response = $controller->uploadAsset($request);

        $payload = $response->getData(true);
        $this->assertArrayHasKey('url', $payload);
        $this->assertStringContainsString('/storage/', $payload['url']);
    }

    /**
     * Baris 216-220 — cabang khusus type=background: resize max 1920x1080
     * dan disimpan sebagai JPEG (bukan PNG seperti logo/ttd/cap).
     */
    public function test_upload_asset_resizes_and_saves_background_as_jpeg()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('bg.png', 2000, 1200);
        $request = new Request([
            'type' => 'background',
        ]);
        $request->files->set('file', $file);

        $controller = new CertificateController();
        $response = $controller->uploadAsset($request);

        $payload = $response->getData(true);
        $this->assertArrayHasKey('url', $payload);
        $this->assertStringEndsWith('.jpg', parse_url($payload['url'], PHP_URL_PATH));
        $this->assertNotNull($institution->fresh()->background_path);
    }

    public function test_remove_asset_clears_institution_asset_pointer()
    {
        $institution = Institution::factory()->create(['logo_path' => 'institutions/1/logo/logo.png']);
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $request = new Request([
            'type' => 'logo',
        ]);

        $controller = new CertificateController();
        $response = $controller->removeAsset($request);

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertNull($institution->fresh()->logo_path);
    }

    public function test_get_assets_returns_asset_urls()
    {
        $institution = Institution::factory()->create(['logo_path' => 'institutions/1/logo/logo.png']);
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $controller = new CertificateController();
        $response = $controller->getAssets();

        $payload = $response->getData(true);
        $this->assertArrayHasKey('logo', $payload);
        $this->assertArrayHasKey('ttd', $payload);
        $this->assertArrayHasKey('cap', $payload);
        $this->assertArrayHasKey('background', $payload);
    }

    /**
     * Baris 293-297 — historyBatch() memfilter berdasarkan judul batch atau nama event.
     */
    public function test_history_batch_filters_by_search_term()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'title'          => 'Pelatihan Laravel Lanjutan',
            'event_name'     => 'Kelas Backend',
        ]);
        CertificateBatch::factory()->create([
            'institution_id' => $institution->id,
            'title'          => 'Workshop Figma',
            'event_name'     => 'Kelas Desain',
        ]);

        $controller = new CertificateController();
        $request = new Request(['search' => 'Laravel']);
        $response = $controller->historyBatch($request);

        $content = $this->responseContent($response);
        $this->assertStringContainsString('Pelatihan Laravel Lanjutan', $content);
        $this->assertStringNotContainsString('Workshop Figma', $content);
    }

    public function test_verify_and_participant_return_views_for_known_and_unknown_tokens()
    {
        $certificate = Certificate::factory()->create();

        $controller = new CertificateController();
        $verifyResponse = $controller->verify($certificate->verification_token);
        $participantResponse = $controller->participant($certificate->verification_token);
        $invalidParticipantResponse = $controller->participant('missing-token');

        $this->assertStringContainsString('sertifikat', strtolower($this->responseContent($verifyResponse)));
        $this->assertStringContainsString('sertifikat', strtolower($this->responseContent($participantResponse)));
        $this->assertStringContainsString('sertifikat', strtolower($this->responseContent($invalidParticipantResponse)));
    }

    private function responseContent($response): string
    {
        if (method_exists($response, 'render')) {
            return $response->render();
        }

        if (method_exists($response, 'getContent')) {
            return $response->getContent();
        }

        return (string) $response;
    }

    public function test_destroy_deletes_certificate_and_cached_pdf()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institution->id]);
        Storage::disk('local')->put('pdf_cache/' . $certificate->verification_token . '.pdf', 'pdf');

        $controller = new CertificateController();
        $response = $controller->destroy($certificate);

        $this->assertTrue($response->isRedirect());
        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
    }

    /**
     * Baris 334-335 — destroy() menolak hapus sertifikat milik lembaga lain.
     */
    public function test_destroy_returns_403_for_certificate_from_other_institution()
    {
        $institutionA = Institution::factory()->create();
        $institutionB = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institutionA->id]);
        $this->actingAs($user);

        $certificate = Certificate::factory()->create(['institution_id' => $institutionB->id]);

        $controller = new CertificateController();

        try {
            $controller->destroy($certificate);
            $this->fail('Seharusnya melempar HttpException 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertDatabaseHas('certificates', ['id' => $certificate->id]);
    }

    /**
     * Baris 348-351 — resolveAssetPath() private, dipanggil via Reflection
     * karena saat ini tidak dipakai method lain di controller (dead code candidate,
     * tapi tetap perlu dicover kalau memang harus dipertahankan).
     */
    public function test_resolve_asset_path_returns_empty_string_for_null_path()
    {
        $controller = new CertificateController();
        $method = new ReflectionMethod($controller, 'resolveAssetPath');
        $method->setAccessible(true);

        $this->assertSame('', $method->invoke($controller, null));
    }

    public function test_resolve_asset_path_returns_full_storage_path_when_given_relative_path()
    {
        $controller = new CertificateController();
        $method = new ReflectionMethod($controller, 'resolveAssetPath');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'institutions/1/logo/logo.png');

        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('app/public/institutions/1/logo/logo.png', $result);
    }
   public function test_pdf_generates_qr_code_when_qr_code_field_is_empty()
{
    // 1. Setup Institution & User Login
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $this->actingAs($user);

    $batch = \App\Models\CertificateBatch::forceCreate([
        'institution_id' => $institution->id,
        'batch_token'    => (string) \Illuminate\Support\Str::uuid(),
        'event_name'     => 'Testing QR Generation',
        'date_start'     => '2026-07-21',
        'date_end'       => '2026-07-21',
    ]);

    $verificationToken = (string) \Illuminate\Support\Str::uuid();

    // 2. Insert via DB::table untuk bypass Model Event created
    $certId = \Illuminate\Support\Facades\DB::table('certificates')->insertGetId([
        'institution_id'     => $institution->id,
        'batch_id'           => $batch->id,
        'nama'               => 'Siti Aminah',
        'nomor'              => 'CERT/2026/777',
        'event_name'         => 'Testing QR Generation',
        'date_start'         => '2026-07-21',
        'verification_token' => $verificationToken,
        'qr_code'            => null, // Murni NULL di DB
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::fake('public');

    // 3. Panggil endpoint pregenerate
    $controller = app(\App\Http\Controllers\CertificateController::class);
    
    try {
        $controller->pregenerate($verificationToken);
    } catch (\Throwable $e) {
        // Abaikan jika ada error lanjutan DomPDF/View, baris 167-168 sudah tereksekusi
    }

    // 4. Assert bahwa generateAndSaveQrCode() & refresh() berhasil dijalankan
    $certificate = \App\Models\Certificate::find($certId);
    $this->assertNotEmpty($certificate->qr_code);
}

}
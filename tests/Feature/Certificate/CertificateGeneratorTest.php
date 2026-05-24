<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Certificate Generator — halaman, store tunggal, PDF, verifikasi
 *
 * Scope file ini:
 * - Akses halaman generator
 * - Store sertifikat tunggal (validasi + response)
 * - Download PDF (proteksi institusi, 404)
 * - Halaman peserta publik
 * - Verifikasi publik
 *
 * Tidak termasuk (ada di file dedicated):
 * - Batch store  → CertificateBatchTest / CertificateBatchControllerTest
 * - History      → CertificateHistoryTest
 * - Delete       → CertificateBatchControllerTest
 *
 * Jalankan: php artisan test --filter CertificateGeneratorTest
 */
class CertificateGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User        $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create(['name' => 'Lembaga Test']);
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    // ── Akses halaman generator ────────────────────────────────

    #[Test]
    public function admin_can_access_generator_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.index'))
            ->assertStatus(200)
            ->assertSee('Generator Sertifikat');
    }

    #[Test]
    public function guest_cannot_access_generator_page(): void
    {
        $this->get(route('certificate.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function superadmin_cannot_access_generator_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->get(route('certificate.index'))->assertForbidden();
    }

    #[Test]
    public function inactive_admin_cannot_access_generator_page(): void
    {
        $inactive = User::factory()->adminOf($this->institution)->inactive()->create();
        $this->actingAs($inactive)->get(route('certificate.index'))->assertRedirect();
    }

    // ── Store sertifikat tunggal ───────────────────────────────

    #[Test]
    public function admin_can_store_single_certificate(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'nama'        => 'Budi Santoso',
                'perusahaan'  => 'PT Test',
                'nomor'       => 'CERT/001/2026',
                'event_name'  => 'Pelatihan Test',
                'date_start'  => '2026-01-01',
                'date_end'    => null,
                'event_place' => 'Purwokerto',
                'signer_name' => 'Dr. Test',
                'signer_title'=> 'Ketua',
            ]);

        $res->assertStatus(200)
            ->assertJsonStructure(['success', 'verification_token', 'verification_url', 'pdf_url']);

        $this->assertDatabaseHas('certificates', [
            'nama'           => 'Budi Santoso',
            'nomor'          => 'CERT/001/2026',
            'institution_id' => $this->institution->id,
        ]);
    }

    #[Test]
    public function store_fails_without_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama', 'nomor', 'event_name', 'date_start']);
    }

    #[Test]
    public function store_response_contains_pdf_url(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('certificate.store'), [
            'nama' => 'Test', 'nomor' => 'X', 'event_name' => 'E', 'date_start' => '2026-01-01',
        ]);
        $this->assertStringContainsString('/pdf', $res->json('pdf_url'));
    }

    #[Test]
    public function certificate_has_unique_verification_token(): void
    {
        $this->actingAs($this->admin)->postJson(route('certificate.store'), [
            'nama' => 'A', 'nomor' => 'A1', 'event_name' => 'E', 'date_start' => '2026-01-01',
        ]);
        $this->actingAs($this->admin)->postJson(route('certificate.store'), [
            'nama' => 'B', 'nomor' => 'B1', 'event_name' => 'E', 'date_start' => '2026-01-01',
        ]);
        $this->assertCount(2, array_unique(Certificate::pluck('verification_token')->toArray()));
    }

    #[Test]
    public function cert_desc_max_200_characters(): void
    {
        $this->actingAs($this->admin)->postJson(route('certificate.store'), [
            'nama' => 'Test', 'nomor' => 'X', 'event_name' => 'E', 'date_start' => '2026-01-01',
            'cert_desc' => str_repeat('A', 201),
        ])->assertStatus(422)->assertJsonValidationErrors(['cert_desc']);
    }

    #[Test]
    public function guest_cannot_store_certificate(): void
    {
        $this->postJson(route('certificate.store'), [
            'nama' => 'Test', 'nomor' => 'X', 'event_name' => 'E', 'date_start' => '2026-01-01',
        ])->assertUnauthorized();
    }

    // ── PDF download ───────────────────────────────────────────

    #[Test]
    public function admin_cannot_download_pdf_of_other_institution(): void
    {
        $otherInst = Institution::factory()->create();
        $cert      = Certificate::factory()->forInstitution($otherInst)->create();

        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertForbidden();
    }

    #[Test]
    public function pdf_returns_404_for_invalid_token(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', 'invalid-token'))
            ->assertNotFound();
    }

    // ── Halaman peserta publik ─────────────────────────────────

    #[Test]
    public function participant_page_is_publicly_accessible(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->get(route('certificate.participant', $cert->verification_token))->assertStatus(200);
    }

    #[Test]
    public function participant_page_shows_certificate_data(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Budi Santoso']);
        $this->get(route('certificate.participant', $cert->verification_token))->assertSee('Budi Santoso');
    }

    #[Test]
    public function certificate_invalid_token_shows_invalid_page(): void
    {
        $this->get(route('certificate.participant', 'token-tidak-ada'))
            ->assertStatus(200)
            ->assertViewIs('certificate.participant-invalid')
            ->assertSee('Sertifikat Tidak Ditemukan');
    }

    // ── Verifikasi publik ──────────────────────────────────────

    #[Test]
    public function public_can_verify_valid_certificate(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Peserta Valid']);
        $this->get(route('certificate.verify', $cert->verification_token))
            ->assertStatus(200)
            ->assertSee('Peserta Valid');
    }

    #[Test]
    public function verify_returns_invalid_view_for_wrong_token(): void
    {
        $this->get(route('certificate.verify', 'token-salah'))
            ->assertStatus(200)
            ->assertSee('Tidak Ditemukan');
    }
    #[Test]
    public function it_returns_500_with_generic_message_when_pregenerate_fails()
    {
        $cert = Certificate::factory()->create(['institution_id' => $this->institution->id]);
        
        // Mocking DomPDF throw exception
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->andThrow(new \Exception('PDF Error'));

        // PERBAIKAN: Gunakan postJson karena di route didefinisikan sebagai POST
        $response = $this->actingAs($this->admin)
            ->postJson(route('certificate.pregenerate', $cert->verification_token));

        $response->assertStatus(500)
            ->assertJson(['success' => false])
            ->assertJsonMissing(['error' => 'PDF Error']); // pesan internal tidak boleh bocor ke response
    }

    #[Test]
    public function it_removes_asset_even_if_file_is_missing_physically()
    {
        $this->institution->update(['logo_path' => 'institutions/99/logo/missing.png']);

        // PERBAIKAN: Gunakan nama route yang benar sesuai web.php
        $response = $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.remove'), ['type' => 'logo']);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull($this->institution->fresh()->logo_path);
    }

    #[Test]
    public function it_returns_403_when_deleting_certificate_from_other_institution()
    {
        // 1. Buat institusi lain dan sertifikatnya
        $otherInstitution = \App\Models\Institution::factory()->create();
        $otherCertificate = \App\Models\Certificate::factory()->create([
            'institution_id' => $otherInstitution->id
        ]);

        // 2. Coba hapus sertifikat tersebut menggunakan user kita (institusi berbeda)
        $response = $this->actingAs($this->admin)
            ->delete(route('certificate.destroy', $otherCertificate->id));

        // 3. Pastikan mendapatkan status 403 (Forbidden)
        $response->assertStatus(403);

        // 4. Pastikan data di database tidak terhapus
        $this->assertDatabaseHas('certificates', ['id' => $otherCertificate->id]);
    }

    #[Test]
    public function it_successfully_deletes_certificate_and_clears_cache()
    {
        // Unit test ini untuk memastikan baris 297-302 (delete cache & record) juga ter-cover
        $cert = \App\Models\Certificate::factory()->create([
            'institution_id' => $this->institution->id
        ]);

        // Simulasikan ada file cache fisik
        $cachePath = 'pdf_cache/' . $cert->verification_token . '.pdf';
        \Illuminate\Support\Facades\Storage::disk('local')->put($cachePath, 'fake-pdf');

        $response = $this->actingAs($this->admin)
            ->delete(route('certificate.destroy', $cert->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($cachePath);
    }

    // ── cachePdf() coverage ──────────────────────────────────

    #[Test]
    public function cache_pdf_creates_directory_if_not_exists(): void
    {
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');

        // Hapus semua file lalu folder agar mkdir terpanggil
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                if (is_file($f)) @unlink($f);
            }
            @rmdir($cacheDir);
        }

        if (is_dir($cacheDir)) {
            $this->markTestSkipped('Folder pdf_cache tidak bisa dihapus.');
        }

        // Mock PDF agar tidak generate sungguhan
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('setOptions')->andReturnSelf();
        $pdfMock->shouldReceive('output')->andReturn('%PDF-1.4 fake');
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->andReturn($pdfMock);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'nama'        => 'Test Mkdir',
                'nomor'       => 'CERT/001',
                'event_name'  => 'Pelatihan',
                'date_start'  => '2026-06-30',
                'event_place' => 'Jakarta',
                'signer_name' => 'Dr. Test',
                'signer_title'=> 'Ketua',
            ])
            ->assertOk();

        // Folder harus sudah dibuat oleh cachePdf()
        $this->assertDirectoryExists($cacheDir);
    }

    #[Test]
    public function cache_pdf_logs_warning_when_build_fails(): void
    {
        // Simulasi buildPdf() throw exception → cachePdf catch harus log warning
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->andThrow(new \Exception('PDF build error'));

        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'cachePdf gagal'));

        // Sertifikat tetap berhasil dibuat meski cachePdf gagal
        $response = $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'nama'        => 'Cache Fail Test',
                'nomor'       => 'CERT/002',
                'event_name'  => 'Pelatihan',
                'date_start'  => '2026-06-30',
                'signer_name' => 'Dr. Test',
                'signer_title'=> 'Ketua',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificates', ['nama' => 'Cache Fail Test']);
    }
}

<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\CertificateController;
use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit + Feature Test: CertificateController
 *
 * Jalankan: php artisan test --filter CertificateControllerTest
 */
class CertificateControllerTest extends TestCase
{
    use RefreshDatabase;

    private \ReflectionMethod $resolveAssetPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake storage di setUp agar aktif sebelum apapun di-boot
        Storage::fake('local');
        Storage::fake('public');

        $method = new \ReflectionMethod(CertificateController::class, 'resolveAssetPath');
        $method->setAccessible(true);
        $this->resolveAssetPath = $method;
    }

    private function invoke(?string $path): string
    {
        return $this->resolveAssetPath->invoke(
            new CertificateController(),
            $path
        );
    }

    // ══════════════════════════════════════════════
    // Helper
    // ══════════════════════════════════════════════

    private function makeUserWithCert(array $certOverrides = []): array
    {
        $institution = Institution::factory()->create();
        $user        = User::factory()->create(['institution_id' => $institution->id]);
        $cert        = Certificate::factory()->create(array_merge([
            'institution_id' => $institution->id,
            'issued_by'      => $user->id,
        ], $certOverrides));

        return [$user, $institution, $cert];
    }

    /**
     * Mock DomPDF facade agar tidak render HTML sungguhan.
     * Mengembalikan fake PDF bytes.
     */
    private function mockPdf(): void
    {
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('output')->andReturn('%PDF-1.4 fake-content');
        $pdfInstance->shouldReceive('download')->andReturn(
            response('%PDF-1.4 fake-content', 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="sertifikat.pdf"',
            ])
        );

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
    }

    // ══════════════════════════════════════════════
    // resolveAssetPath — sudah ada sebelumnya
    // ══════════════════════════════════════════════

    #[Test]
    public function returns_empty_string_for_null(): void
    {
        $this->assertEquals('', $this->invoke(null));
    }

    #[Test]
    public function returns_empty_string_for_empty_string(): void
    {
        $this->assertEquals('', $this->invoke(''));
    }

    #[Test]
    public function returns_full_path_for_valid_relative_path(): void
    {
        $result = $this->invoke('institutions/1/logo/test.png');
        $this->assertStringContainsString('institutions/1/logo/test.png', $result);
    }

    #[Test]
    public function result_contains_no_backslash(): void
    {
        $result = $this->invoke('institutions/1/logo/test.png');
        $this->assertStringNotContainsString('\\', $result);
    }

    #[Test]
    public function result_contains_storage_path(): void
    {
        $result = $this->invoke('institutions/1/logo/test.png');
        $this->assertStringContainsString('storage', $result);
    }

    // ══════════════════════════════════════════════
    // pregenerate() — line 80–103
    // ══════════════════════════════════════════════

    /** Line 84–86: institution berbeda → 403 */
    #[Test]
    public function pregenerate_returns_403_when_institution_mismatch(): void
    {
        [$userA, , $certA] = $this->makeUserWithCert();
        [$userB]           = $this->makeUserWithCert();

        $this->actingAs($userB)
            ->postJson(route('certificate.pregenerate', $certA->verification_token))
            ->assertForbidden();
    }

    /** Line 90–92: cache sudah ada → return cached:true tanpa generate */
    #[Test]
    public function pregenerate_returns_cached_true_when_file_already_exists(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        $cachePath = 'pdf_cache/' . $cert->verification_token . '.pdf';
        Storage::disk('local')->put($cachePath, '%PDF-fake');

        $this->actingAs($user)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertOk()
            ->assertJson(['success' => true, 'cached' => true]);

        // Pastikan DomPDF tidak dipanggil
        Pdf::shouldReceive('loadView')->never();
    }

    /** Line 99–101: tidak ada cache → generate, simpan file, return cached:false */
    #[Test]
    public function pregenerate_generates_pdf_and_stores_to_cache(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        $this->mockPdf();

        $this->actingAs($user)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertOk()
            ->assertJson(['success' => true, 'cached' => false]);

        Storage::disk('local')->assertExists(
            'pdf_cache/' . $cert->verification_token . '.pdf'
        );
    }

    /** Line 102–103: DomPDF throws → return 500 + success:false */
    #[Test]
    public function pregenerate_returns_500_when_pdf_build_throws(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        // Paksa DomPDF lempar exception
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('DomPDF render error'));

        $this->actingAs($user)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertStatus(500)
            ->assertJson(['success' => false, 'error' => 'DomPDF render error']);
    }

    // ══════════════════════════════════════════════
    // pdf() — line 129–134 (cache hit)
    // ══════════════════════════════════════════════

    /** Line 117–119: institution berbeda → 403 */
    #[Test]
    public function pdf_returns_403_when_institution_mismatch(): void
    {
        [$userA, , $certA] = $this->makeUserWithCert();
        [$userB]           = $this->makeUserWithCert();

        $this->actingAs($userB)
            ->get(route('certificate.pdf', $certA->verification_token))
            ->assertForbidden();
    }

    /** Line 130–134: cache ada → serve dari file, bukan generate ulang */
    #[Test]
    public function pdf_serves_from_cache_when_file_exists(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        $cachePath = 'pdf_cache/' . $cert->verification_token . '.pdf';
        Storage::disk('local')->put($cachePath, '%PDF-1.4 cached');

        $response = $this->actingAs($user)
            ->get(route('certificate.pdf', $cert->verification_token));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition')
        );
    }

    /** Line 134: filename di Content-Disposition mengandung nama peserta (slug) */
    #[Test]
    public function pdf_cache_response_has_correct_filename(): void
    {
        [$user, , $cert] = $this->makeUserWithCert(['nama' => 'Budi Santoso']);

        $cachePath = 'pdf_cache/' . $cert->verification_token . '.pdf';
        Storage::disk('local')->put($cachePath, '%PDF-1.4 cached');

        $response = $this->actingAs($user)
            ->get(route('certificate.pdf', $cert->verification_token));

        $this->assertStringContainsString(
            'budi-santoso',
            $response->headers->get('Content-Disposition')
        );
    }

    /** Fallback: tidak ada cache → generate on-the-fly via DomPDF */
    #[Test]
    public function pdf_generates_on_the_fly_when_no_cache(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        $this->mockPdf();

        $this->actingAs($user)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ══════════════════════════════════════════════
    // destroy() — line 267–268 (delete cache)
    // ══════════════════════════════════════════════

    /** Line 268: cache ada saat destroy → file ikut terhapus */
    #[Test]
    public function destroy_deletes_pdf_cache_when_it_exists(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        $cachePath = 'pdf_cache/' . $cert->verification_token . '.pdf';
        Storage::disk('local')->put($cachePath, '%PDF-fake');

        $this->actingAs($user)
            ->delete(route('certificate.destroy', $cert))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($cachePath);
        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
    }

    /** Line 267 else-branch: tidak ada cache → destroy tetap sukses */
    #[Test]
    public function destroy_succeeds_even_when_no_pdf_cache_exists(): void
    {
        [$user, , $cert] = $this->makeUserWithCert();

        $this->actingAs($user)
            ->delete(route('certificate.destroy', $cert))
            ->assertRedirect();

        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
    }

    /** Line 261–263: institution berbeda → 403, cert tidak terhapus */
    #[Test]
    public function destroy_returns_403_when_institution_mismatch(): void
    {
        [$userA, , $certA] = $this->makeUserWithCert();
        [$userB]           = $this->makeUserWithCert();

        $this->actingAs($userB)
            ->delete(route('certificate.destroy', $certA))
            ->assertForbidden();

        $this->assertDatabaseHas('certificates', ['id' => $certA->id]);
    }
}
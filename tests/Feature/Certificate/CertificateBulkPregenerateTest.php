<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: storeBulk (legacy sync) & pregenerate
 *
 * storeBulk → POST /dashboard/certificates/bulk
 * pregenerate → POST /dashboard/certificates/pregenerate/{token}
 *
 * Jalankan: php artisan test --filter CertificateBulkPregenerateTest
 */
class CertificateBulkPregenerateTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->institution = Institution::factory()->create();
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    private function mockPdf(): void
    {
        $pdfInstance = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfInstance->shouldReceive('setPaper')->andReturnSelf();
        $pdfInstance->shouldReceive('setOptions')->andReturnSelf();
        $pdfInstance->shouldReceive('output')->andReturn('%PDF-1.4 fake');

        Pdf::shouldReceive('loadView')->andReturn($pdfInstance);
    }

    // ══════════════════════════════════════════════
    // storeBulk — POST /dashboard/certificates/bulk
    // ══════════════════════════════════════════════

    #[Test]
    public function guest_cannot_storeBulk(): void
    {
        $this->postJson(route('certificate.storeBulk'), [])->assertUnauthorized();
    }

    #[Test]
    public function storeBulk_requires_participants(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'event_name' => 'Test',
                'date_start'   => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participants']);
    }

    #[Test]
    public function storeBulk_requires_participant_nama(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [['nomor' => 'X']],
                'event_name'   => 'Test',
                'date_start'   => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participants.0.nama']);
    }

    #[Test]
    public function storeBulk_creates_all_certificates(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [
                    ['nama' => 'Peserta A', 'nomor' => 'CERT/001/2026', 'perusahaan' => 'PT A'],
                    ['nama' => 'Peserta B', 'nomor' => 'CERT/002/2026', 'perusahaan' => null],
                    ['nama' => 'Peserta C', 'nomor' => 'CERT/003/2026', 'perusahaan' => 'PT C'],
                ],
                'event_name'  => 'Workshop Sinkron',
                'date_start'   => '2026-01-01',
                'event_place' => 'Bandung',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 3])
            ->assertJsonStructure(['success', 'count', 'certificates']);

        $this->assertDatabaseCount('certificates', 3);
        $this->assertDatabaseHas('certificates', ['nama' => 'Peserta A', 'institution_id' => $this->institution->id]);
    }

    #[Test]
    public function storeBulk_links_certificates_to_correct_institution(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [
                    ['nama' => 'Peserta X', 'nomor' => 'CERT/001/2026'],
                ],
                'event_name' => 'Test',
                'date_start'   => '2026-01-01',
            ])
            ->assertOk();

        $cert = Certificate::first();
        $this->assertEquals($this->institution->id, $cert->institution_id);
    }

    #[Test]
    public function storeBulk_response_contains_verification_urls(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [
                    ['nama' => 'Peserta Y', 'nomor' => 'CERT/001/2026'],
                ],
                'event_name' => 'Test',
                'date_start'   => '2026-01-01',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'certificates' => [
                    '*' => ['nama', 'nomor', 'verification_url', 'verification_token', 'pdf_url'],
                ],
            ]);
    }

    #[Test]
    public function storeBulk_applies_shared_event_data_to_all_certs(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [
                    ['nama' => 'A', 'nomor' => 'CERT/001'],
                    ['nama' => 'B', 'nomor' => 'CERT/002'],
                ],
                'event_name'   => 'Seminar Bersama',
                'date_start'   => '2026-01-01',
                'signer_name'  => 'Dr. Fulan',
                'signer_title' => 'Direktur',
            ])
            ->assertOk();

        Certificate::all()->each(function ($cert) {
            $this->assertEquals('Seminar Bersama', $cert->event_name);
            $this->assertEquals('2026-01-01',       $cert->date_start?->format('Y-m-d'));
            $this->assertEquals('Dr. Fulan',        $cert->signer_name);
            $this->assertEquals('Direktur',          $cert->signer_title);
        });
    }

    #[Test]
    public function storeBulk_each_certificate_has_unique_token(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [
                    ['nama' => 'P1', 'nomor' => 'CERT/001'],
                    ['nama' => 'P2', 'nomor' => 'CERT/002'],
                    ['nama' => 'P3', 'nomor' => 'CERT/003'],
                ],
                'event_name' => 'Test Unique Token',
                'date_start'   => '2026-01-01',
            ])
            ->assertOk();

        $tokens = Certificate::pluck('verification_token')->unique();
        $this->assertCount(3, $tokens);
    }

    #[Test]
    public function storeBulk_event_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.storeBulk'), [
                'participants' => [['nama' => 'P1', 'nomor' => 'X']],
                'date_start'   => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event_name']);
    }

    // ══════════════════════════════════════════════
    // pregenerate — POST /dashboard/certificates/pregenerate/{token}
    // ══════════════════════════════════════════════

    #[Test]
    public function guest_cannot_pregenerate(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertUnauthorized();
    }

    #[Test]
    public function pregenerate_returns_403_for_other_institution_cert(): void
    {
        $otherInst = Institution::factory()->create();
        $cert      = Certificate::factory()->forInstitution($otherInst)->create();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertForbidden();
    }

    #[Test]
    public function pregenerate_returns_cached_true_when_pdf_already_exists(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        Storage::disk('local')->put('pdf_cache/' . $cert->verification_token . '.pdf', '%PDF-fake');

        $this->actingAs($this->admin)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertOk()
            ->assertJson(['success' => true, 'cached' => true]);
    }

    #[Test]
    public function pregenerate_generates_and_caches_pdf_when_not_exists(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->mockPdf();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertOk()
            ->assertJson(['success' => true, 'cached' => false]);

        Storage::disk('local')->assertExists('pdf_cache/' . $cert->verification_token . '.pdf');
    }

    #[Test]
    public function pregenerate_returns_404_for_invalid_token(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.pregenerate', 'token-tidak-ada'))
            ->assertNotFound();
    }

    #[Test]
    public function pregenerate_returns_500_when_pdf_build_fails(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('PDF error'));

        $this->actingAs($this->admin)
            ->postJson(route('certificate.pregenerate', $cert->verification_token))
            ->assertStatus(500)
            ->assertJson(['success' => false]);
    }
}
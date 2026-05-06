<?php

namespace Tests\Unit\Models;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Certificate Model
 *
 * Jalankan: php artisan test --filter CertificateModelTest
 */
class CertificateModelTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create(['name' => 'Test Institution']);
    }

    // ── Auto UUID token ─────────────────────────────────────────

    #[Test]
    public function certificate_auto_generates_verification_token(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->assertNotNull($cert->verification_token);
        $this->assertEquals(36, strlen($cert->verification_token));
    }

    #[Test]
    public function each_certificate_has_unique_token(): void
    {
        $certs = Certificate::factory()->forInstitution($this->institution)->count(5)->create();
        $tokens = $certs->pluck('verification_token')->unique();
        $this->assertCount(5, $tokens);
    }

    #[Test]
    public function custom_token_is_not_overridden(): void
    {
        $token = '12345678-1234-1234-1234-123456789012'; // valid UUID 36 chars
        $cert  = Certificate::factory()->forInstitution($this->institution)->create([
            'verification_token' => $token,
        ]);
        $this->assertEquals($token, $cert->fresh()->verification_token);
    }

    // ── Relasi ──────────────────────────────────────────────────

    #[Test]
    public function certificate_belongs_to_institution(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->assertInstanceOf(Institution::class, $cert->institution);
        $this->assertEquals($this->institution->id, $cert->institution->id);
    }

    #[Test]
    public function certificate_belongs_to_issued_by_user(): void
    {
        $admin = User::factory()->adminOf($this->institution)->create();
        $cert  = Certificate::factory()
            ->forInstitution($this->institution)
            ->issuedBy($admin)
            ->create();

        $this->assertInstanceOf(User::class, $cert->issuedBy);
        $this->assertEquals($admin->id, $cert->issuedBy->id);
    }

    #[Test]
    public function certificate_issued_by_can_be_null(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'issued_by' => null,
        ]);
        $this->assertNull($cert->issuedBy);
    }

    // ── URL Helper ──────────────────────────────────────────────

    #[Test]
    public function verification_url_contains_token(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->assertStringContainsString($cert->verification_token, $cert->verificationUrl());
    }

    #[Test]
    public function verification_url_contains_verify_path(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->assertStringContainsString('/verify/', $cert->verificationUrl());
    }

    // ── Scopes ──────────────────────────────────────────────────

    #[Test]
    public function scope_for_institution_filters_correctly(): void
    {
        $otherInst = Institution::factory()->create();

        Certificate::factory()->forInstitution($this->institution)->count(3)->create();
        Certificate::factory()->forInstitution($otherInst)->count(2)->create();

        $results = Certificate::forInstitution($this->institution->id)->get();
        $this->assertCount(3, $results);
        $results->each(fn($c) => $this->assertEquals($this->institution->id, $c->institution_id));
    }

    #[Test]
    public function scope_search_finds_by_nama(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Budi Santoso']);
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Citra Lestari']);

        $results = Certificate::forInstitution($this->institution->id)->search('Budi')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Budi Santoso', $results->first()->nama);
    }

    #[Test]
    public function scope_search_finds_by_nomor(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nomor' => 'CERT/999/2026']);
        Certificate::factory()->forInstitution($this->institution)->create(['nomor' => 'CERT/001/2026']);

        $results = Certificate::forInstitution($this->institution->id)->search('999')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('CERT/999/2026', $results->first()->nomor);
    }

    #[Test]
    public function scope_search_finds_by_event_name(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['event_name' => 'Pelatihan Khusus']);
        Certificate::factory()->forInstitution($this->institution)->create(['event_name' => 'Seminar Umum']);

        $results = Certificate::forInstitution($this->institution->id)->search('Khusus')->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function scope_search_finds_by_perusahaan(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['perusahaan' => 'PT Maju Jaya']);
        Certificate::factory()->forInstitution($this->institution)->create(['perusahaan' => 'CV Sukses']);

        $results = Certificate::forInstitution($this->institution->id)->search('Maju')->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function scope_search_returns_empty_for_no_match(): void
    {
        Certificate::factory()->forInstitution($this->institution)->count(3)->create();

        $results = Certificate::forInstitution($this->institution->id)->search('ZZZZNOTFOUND')->get();
        $this->assertCount(0, $results);
    }

    // ── Fillable / Casts ────────────────────────────────────────

    #[Test]
    public function issued_at_is_cast_to_datetime(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cert->issued_at);
    }

    #[Test]
    public function certificate_can_have_null_perusahaan(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'perusahaan' => null,
        ]);
        $this->assertNull($cert->perusahaan);
    }

    #[Test]
    public function certificate_can_have_null_cert_desc(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'cert_desc' => null,
        ]);
        $this->assertNull($cert->cert_desc);
    }
}

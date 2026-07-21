<?php

namespace Tests\Unit\Iterasi2;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Certificate Model — Iterasi 2
 *
 * Lingkup: relasi institution()/issuedBy(), scope pencarian, snapshotAssets(),
 * dan resolusi path asset (resolveStoragePath, resolvedLogoPath, dst).
 * Sesuai US-12 (Generate PDF per Peserta).
 *
 * Catatan: verification_token, qr_code, dan URL helper publik (verificationUrl,
 * participantUrl, pdfUrl) diuji di Iterasi 4 karena baru menjadi fitur resmi
 * pada iterasi tersebut (QR Code & Verifikasi Publik). Relasi batch() diuji di
 * Iterasi 3 karena CertificateBatch baru ada di iterasi tersebut.
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
        $this->institution = Institution::factory()->create();
    }

    // ══════════════════════════════════════════════
    // Relasi
    // ══════════════════════════════════════════════

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
        $cert  = Certificate::factory()->forInstitution($this->institution)->issuedBy($admin)->create();

        $this->assertInstanceOf(User::class, $cert->issuedBy);
        $this->assertEquals($admin->id, $cert->issuedBy->id);
    }

    #[Test]
    public function issued_by_is_null_when_not_set(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create(['issued_by' => null]);

        $this->assertNull($cert->issuedBy);
    }

    // ══════════════════════════════════════════════
    // scopeForInstitution()
    // ══════════════════════════════════════════════

    #[Test]
    public function scope_for_institution_returns_only_that_institution_certificates(): void
    {
        $otherInstitution = Institution::factory()->create();

        Certificate::factory()->forInstitution($this->institution)->count(3)->create();
        Certificate::factory()->forInstitution($otherInstitution)->count(2)->create();

        $result = Certificate::forInstitution($this->institution->id)->get();

        $this->assertCount(3, $result);
        $result->each(fn ($c) => $this->assertEquals($this->institution->id, $c->institution_id));
    }

    // ══════════════════════════════════════════════
    // scopeSearch()
    // ══════════════════════════════════════════════

    #[Test]
    public function scope_search_matches_by_nama(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Budi Santoso']);
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Siti Aminah']);

        $result = Certificate::search('Budi')->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Budi Santoso', $result->first()->nama);
    }

    #[Test]
    public function scope_search_matches_by_nomor(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nomor' => 'CERT/999/2026']);

        $result = Certificate::search('999')->get();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function scope_search_matches_by_event_name(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['event_name' => 'Seminar Nasional AI']);

        $result = Certificate::search('Nasional')->get();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function scope_search_matches_by_perusahaan(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['perusahaan' => 'CV Oemah Website']);

        $result = Certificate::search('Oemah')->get();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function scope_search_returns_empty_when_no_match(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Budi Santoso']);

        $this->assertCount(0, Certificate::search('Tidak Ada')->get());
    }

    // ══════════════════════════════════════════════
    // snapshotAssets()
    // ══════════════════════════════════════════════

    #[Test]
    public function snapshot_assets_copies_institution_asset_paths_to_certificate(): void
    {
        $institution = Institution::factory()->create([
            'logo_path'        => 'institutions/1/logo.png',
            'ttd_path'         => 'institutions/1/ttd.png',
            'cap_path'         => 'institutions/1/cap.png',
            'background_path'  => 'institutions/1/bg.png',
        ]);

        $cert = Certificate::factory()->forInstitution($institution)->create([
            'snap_logo_path' => null,
            'snap_ttd_path'  => null,
            'snap_cap_path'  => null,
            'snap_bg_path'   => null,
        ]);

        $cert->snapshotAssets($institution);
        $cert->refresh();

        $this->assertEquals('institutions/1/logo.png', $cert->snap_logo_path);
        $this->assertEquals('institutions/1/ttd.png', $cert->snap_ttd_path);
        $this->assertEquals('institutions/1/cap.png', $cert->snap_cap_path);
        $this->assertEquals('institutions/1/bg.png', $cert->snap_bg_path);
    }

    #[Test]
    public function snapshot_assets_remains_unchanged_if_institution_assets_change_afterward(): void
    {
        $institution = Institution::factory()->create(['logo_path' => 'original-logo.png']);
        $cert = Certificate::factory()->forInstitution($institution)->create();
        $cert->snapshotAssets($institution);

        // Admin lembaga ganti logo di kemudian hari
        $institution->update(['logo_path' => 'new-logo.png']);

        $cert->refresh();
        $this->assertEquals('original-logo.png', $cert->snap_logo_path);
    }

    // ══════════════════════════════════════════════
    // resolveStoragePath()
    // ══════════════════════════════════════════════

    #[Test]
    public function resolve_storage_path_returns_empty_string_for_null(): void
    {
        $this->assertEquals('', Certificate::resolveStoragePath(null));
    }

    #[Test]
    public function resolve_storage_path_uses_public_storage_path_for_system_background(): void
    {
        $path = Certificate::resolveStoragePath('backgrounds/system/classic.jpg');

        $this->assertStringContainsString('public/storage/backgrounds/system/classic.jpg', $path);
    }

    #[Test]
    public function resolve_storage_path_uses_app_public_path_for_institution_asset(): void
    {
        $path = Certificate::resolveStoragePath('institutions/1/logo.png');

        $this->assertStringContainsString('storage/app/public/institutions/1/logo.png', $path);
    }

    #[Test]
    public function resolve_storage_path_never_returns_backslashes(): void
    {
        $path = Certificate::resolveStoragePath('institutions/1/logo.png');

        $this->assertStringNotContainsString('\\', $path);
    }

    // ══════════════════════════════════════════════
    // resolvedXPath() delegators
    // ══════════════════════════════════════════════

    #[Test]
    public function resolved_logo_path_delegates_to_resolve_storage_path(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'snap_logo_path' => 'institutions/1/logo.png',
        ]);

        $this->assertStringContainsString('institutions/1/logo.png', $cert->resolvedLogoPath());
    }

    #[Test]
    public function resolved_ttd_path_returns_empty_string_when_snapshot_missing(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'snap_ttd_path' => null,
        ]);

        $this->assertEquals('', $cert->resolvedTtdPath());
    }

    #[Test]
    public function resolved_cap_path_delegates_to_resolve_storage_path(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'snap_cap_path' => 'institutions/1/cap.png',
        ]);

        $this->assertStringContainsString('institutions/1/cap.png', $cert->resolvedCapPath());
    }

    #[Test]
    public function resolved_bg_path_uses_system_prefix_when_snapshot_is_system_background(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'snap_bg_path' => 'backgrounds/system/classic.jpg',
        ]);

        $this->assertStringContainsString('public/storage/backgrounds/system/classic.jpg', $cert->resolvedBgPath());
    }
}

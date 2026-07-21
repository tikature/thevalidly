<?php

namespace Tests\Unit\Iterasi2;

use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Institution — Asset URL Helper — Iterasi 2
 *
 * Lingkup: logoUrl(), ttdUrl(), capUrl(), backgroundUrl(), dan relasi certificates().
 * Sesuai US-10 (Upload Aset Visual Lembaga).
 *
 * Jalankan: php artisan test --filter InstitutionAssetTest
 */
class InstitutionAssetTest extends TestCase
{
    use RefreshDatabase;

    // ── Relasi certificates() ────────────────────────────────────

    #[Test]
    public function institution_has_many_certificates(): void
    {
        $institution = Institution::factory()->create();
        Certificate::factory()->forInstitution($institution)->count(5)->create();

        $this->assertCount(5, $institution->certificates);
    }

    // ── Asset URL Helpers ────────────────────────────────────────

    #[Test]
    public function logo_url_returns_null_when_no_logo(): void
    {
        $institution = Institution::factory()->create(['logo_path' => null]);
        $this->assertNull($institution->logoUrl());
    }

    #[Test]
    public function ttd_url_returns_null_when_no_ttd(): void
    {
        $institution = Institution::factory()->create(['ttd_path' => null]);
        $this->assertNull($institution->ttdUrl());
    }

    #[Test]
    public function cap_url_returns_null_when_no_cap(): void
    {
        $institution = Institution::factory()->create(['cap_path' => null]);
        $this->assertNull($institution->capUrl());
    }

    #[Test]
    public function background_url_returns_null_when_no_background(): void
    {
        $institution = Institution::factory()->create(['background_path' => null]);
        $this->assertNull($institution->backgroundUrl());
    }

    #[Test]
    public function logo_url_returns_string_when_logo_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('institutions/1/logo/test.png', 'fake');

        $institution = Institution::factory()->create([
            'logo_path' => 'institutions/1/logo/test.png',
        ]);

        $this->assertIsString($institution->logoUrl());
        $this->assertStringContainsString('test.png', $institution->logoUrl());
    }

    #[Test]
    public function ttd_url_returns_string_when_ttd_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('institutions/1/ttd/test.png', 'fake');

        $institution = Institution::factory()->create([
            'ttd_path' => 'institutions/1/ttd/test.png',
        ]);

        $this->assertIsString($institution->ttdUrl());
    }

    #[Test]
    public function cap_url_returns_string_when_cap_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('institutions/1/cap/test.png', 'fake');

        $institution = Institution::factory()->create([
            'cap_path' => 'institutions/1/cap/test.png',
        ]);

        $this->assertIsString($institution->capUrl());
        $this->assertStringContainsString('test.png', $institution->capUrl());
    }

    #[Test]
    public function background_url_returns_string_when_background_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('institutions/1/background/test.png', 'fake');

        $institution = Institution::factory()->create([
            'background_path' => 'institutions/1/background/test.png',
        ]);

        $this->assertIsString($institution->backgroundUrl());
        $this->assertStringContainsString('test.png', $institution->backgroundUrl());
    }

    #[Test]
    public function institution_fillable_includes_asset_paths(): void
    {
        $institution = new Institution();
        $fillable    = $institution->getFillable();

        $this->assertContains('logo_path', $fillable);
        $this->assertContains('ttd_path', $fillable);
        $this->assertContains('cap_path', $fillable);
        $this->assertContains('background_path', $fillable);
    }
}

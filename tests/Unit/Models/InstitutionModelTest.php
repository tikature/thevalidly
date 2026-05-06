<?php

namespace Tests\Unit\Models;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Institution Model
 *
 * Jalankan: php artisan test --filter InstitutionModelTest
 */
class InstitutionModelTest extends TestCase
{
    use RefreshDatabase;

    // ── Relasi ──────────────────────────────────────────────────

    #[Test]
    public function institution_has_many_users(): void
    {
        $institution = Institution::factory()->create();
        User::factory()->adminOf($institution)->count(3)->create();

        $this->assertCount(3, $institution->users);
    }

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

    // ── Casts ───────────────────────────────────────────────────

    #[Test]
    public function is_active_is_cast_to_boolean(): void
    {
        $institution = Institution::factory()->create(['is_active' => true]);
        $this->assertIsBool($institution->is_active);
        $this->assertTrue($institution->is_active);
    }

    #[Test]
    public function institution_can_be_inactive(): void
    {
        $institution = Institution::factory()->create(['is_active' => false]);
        $this->assertFalse($institution->is_active);
    }

    // ── Fillable ────────────────────────────────────────────────

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

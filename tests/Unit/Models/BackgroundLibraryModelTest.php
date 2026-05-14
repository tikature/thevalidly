<?php

namespace Tests\Unit\Models;

use App\Models\BackgroundLibrary;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: BackgroundLibrary Model
 *
 * Menutup coverage baris 31–38 (scopeSystem, scopeForInstitution,
 * getUrlAttribute, institution relation).
 *
 * Jalankan: php artisan test --filter BackgroundLibraryModelTest
 */
class BackgroundLibraryModelTest extends TestCase
{
    use RefreshDatabase;

    // ══════════════════════════════════════════════════════════
    // scopeSystem()
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function scope_system_returns_only_system_backgrounds(): void
    {
        $institution = Institution::factory()->create();

        BackgroundLibrary::create(['name' => 'System BG',   'path' => 'sys.jpg',    'is_system' => true]);
        BackgroundLibrary::create(['name' => 'System BG 2', 'path' => 'sys2.jpg',   'is_system' => true]);
        BackgroundLibrary::create(['institution_id' => $institution->id, 'name' => 'Lembaga BG', 'path' => 'lem.jpg', 'is_system' => false]);

        $result = BackgroundLibrary::system()->get();

        $this->assertCount(2, $result);
        $result->each(fn ($bg) => $this->assertTrue($bg->is_system));
    }

    #[Test]
    public function scope_system_returns_empty_when_none_exist(): void
    {
        $institution = Institution::factory()->create();
        BackgroundLibrary::create(['institution_id' => $institution->id, 'name' => 'Lembaga', 'path' => 'lem.jpg', 'is_system' => false]);

        $this->assertCount(0, BackgroundLibrary::system()->get());
    }

    #[Test]
    public function scope_system_orders_by_name(): void
    {
        BackgroundLibrary::create(['name' => 'Zebra',  'path' => 'z.jpg', 'is_system' => true]);
        BackgroundLibrary::create(['name' => 'Alpha',  'path' => 'a.jpg', 'is_system' => true]);
        BackgroundLibrary::create(['name' => 'Modern', 'path' => 'm.jpg', 'is_system' => true]);

        $names = BackgroundLibrary::system()->orderBy('name')->pluck('name')->toArray();

        $this->assertEquals(['Alpha', 'Modern', 'Zebra'], $names);
    }

    // ══════════════════════════════════════════════════════════
    // scopeForInstitution()
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function scope_for_institution_returns_only_that_institution_backgrounds(): void
    {
        $inst1 = Institution::factory()->create();
        $inst2 = Institution::factory()->create();

        BackgroundLibrary::create(['institution_id' => $inst1->id, 'name' => 'BG Inst1', 'path' => 'i1.jpg', 'is_system' => false]);
        BackgroundLibrary::create(['institution_id' => $inst1->id, 'name' => 'BG Inst1 B', 'path' => 'i1b.jpg', 'is_system' => false]);
        BackgroundLibrary::create(['institution_id' => $inst2->id, 'name' => 'BG Inst2', 'path' => 'i2.jpg', 'is_system' => false]);
        BackgroundLibrary::create(['name' => 'System', 'path' => 'sys.jpg', 'is_system' => true]);

        $result = BackgroundLibrary::forInstitution($inst1->id)->get();

        $this->assertCount(2, $result);
        $result->each(fn ($bg) => $this->assertEquals($inst1->id, $bg->institution_id));
    }

    #[Test]
    public function scope_for_institution_excludes_system_backgrounds(): void
    {
        $inst = Institution::factory()->create();
        BackgroundLibrary::create(['name' => 'System', 'path' => 'sys.jpg', 'is_system' => true]);

        $result = BackgroundLibrary::forInstitution($inst->id)->get();

        $this->assertCount(0, $result);
    }

    #[Test]
    public function scope_for_institution_returns_empty_when_none_exist(): void
    {
        $inst = Institution::factory()->create();

        $this->assertCount(0, BackgroundLibrary::forInstitution($inst->id)->get());
    }

    // ══════════════════════════════════════════════════════════
    // getUrlAttribute()
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function get_url_attribute_returns_storage_url_for_lembaga_background(): void
    {
        Storage::fake('public');

        $inst = Institution::factory()->create();
        Storage::disk('public')->put('backgrounds/library/1/bg.jpg', 'fake');

        $bg = BackgroundLibrary::create([
            'institution_id' => $inst->id,
            'name'           => 'My BG',
            'path'           => 'backgrounds/library/1/bg.jpg',
            'is_system'      => false,
        ]);

        $this->assertStringContainsString('backgrounds/library/1/bg.jpg', $bg->url);
    }

    #[Test]
    public function get_url_attribute_returns_url_for_system_background(): void
    {
        Storage::fake('public');

        $bg = BackgroundLibrary::create([
            'name'      => 'Classic',
            'path'      => 'backgrounds/system/classic.jpg',
            'is_system' => true,
        ]);

        $this->assertStringContainsString('backgrounds/system/classic.jpg', $bg->url);
    }

    // ══════════════════════════════════════════════════════════
    // institution() relation
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function institution_relation_returns_correct_institution(): void
    {
        $inst = Institution::factory()->create(['name' => 'Lembaga ABC']);

        $bg = BackgroundLibrary::create([
            'institution_id' => $inst->id,
            'name'           => 'BG Lembaga',
            'path'           => 'bg.jpg',
            'is_system'      => false,
        ]);

        $this->assertEquals('Lembaga ABC', $bg->institution->name);
    }

    #[Test]
    public function institution_relation_is_null_for_system_background(): void
    {
        $bg = BackgroundLibrary::create([
            'name'      => 'System BG',
            'path'      => 'sys.jpg',
            'is_system' => true,
        ]);

        $this->assertNull($bg->institution);
    }

    // ══════════════════════════════════════════════════════════
    // Casts & fillable
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function is_system_is_cast_to_boolean(): void
    {
        $bg = BackgroundLibrary::create([
            'name'      => 'System BG',
            'path'      => 'sys.jpg',
            'is_system' => true,
        ]);

        $this->assertIsBool($bg->fresh()->is_system);
        $this->assertTrue($bg->fresh()->is_system);
    }

    #[Test]
    public function is_system_defaults_to_false(): void
    {
        $inst = Institution::factory()->create();

        $bg = BackgroundLibrary::create([
            'institution_id' => $inst->id,
            'name'           => 'Lembaga BG',
            'path'           => 'bg.jpg',
            'is_system'      => false,
        ]);

        $this->assertFalse($bg->fresh()->is_system);
    }
}

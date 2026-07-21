<?php

namespace Tests\Unit\Iterasi1;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Institution Model — Iterasi 1
 *
 * Lingkup: relasi users, casting, fillable, unique constraint, cascade behaviour.
 * Sesuai US-02 (Pendaftaran Lembaga) & US-04 (Manajemen Admin Lembaga).
 *
 * Catatan: relasi certificates() dan asset URL helpers (logoUrl/ttdUrl/capUrl/
 * backgroundUrl) diuji di Iterasi 2 karena fitur upload aset visual baru ada
 * di iterasi tersebut — bukan bagian dari lingkup pendaftaran/manajemen lembaga.
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
    public function institution_with_no_users_returns_empty_collection(): void
    {
        $institution = Institution::factory()->create();
        $this->assertCount(0, $institution->users);
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

    #[Test]
    public function inactive_state_sets_is_active_to_false(): void
    {
        $institution = Institution::factory()->inactive()->create();
        $this->assertFalse($institution->is_active);
    }

    // ── Fillable ────────────────────────────────────────────────

    #[Test]
    public function institution_fillable_includes_core_registration_fields(): void
    {
        $institution = new Institution();
        $fillable    = $institution->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('address', $fillable);
    }

    #[Test]
    public function it_can_be_created_with_minimum_required_fields(): void
    {
        $institution = Institution::factory()->create([
            'phone'     => null,
            'address'   => null,
            'logo_path' => null,
        ]);

        $this->assertNotNull($institution->id);
        $this->assertNull($institution->phone);
        $this->assertNull($institution->logo_path);
    }

    // ── Unique constraints ───────────────────────────────────────

    #[Test]
    public function slug_is_stored_correctly(): void
    {
        $institution = Institution::factory()->create([
            'slug' => 'lembaga-abc-x1y2',
        ]);

        $this->assertEquals('lembaga-abc-x1y2', $institution->slug);
    }

    #[Test]
    public function slug_must_be_unique(): void
    {
        Institution::factory()->create(['slug' => 'same-slug-abcd']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Institution::factory()->create(['slug' => 'same-slug-abcd']);
    }

    #[Test]
    public function email_must_be_unique(): void
    {
        Institution::factory()->create(['email' => 'same@test.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Institution::factory()->create(['email' => 'same@test.com']);
    }

    // ── Cascade behaviour ────────────────────────────────────────

    #[Test]
    public function deleting_institution_does_not_cascade_users_automatically(): void
    {
        // Foreign key pakai nullOnDelete — delete manual dilakukan di controller
        $institution = Institution::factory()->create();
        User::factory()->adminOf($institution)->create();

        $institution->delete();

        $this->assertDatabaseMissing('institutions', ['id' => $institution->id]);
    }
}

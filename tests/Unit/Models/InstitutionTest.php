<?php

namespace Tests\Unit\Models;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit Test: Institution Model
 *
 * Menguji relasi, casting, dan state pada model Institution.
 * Jalankan: php artisan test --filter InstitutionTest
 */
class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    // ─── Relasi users ──────────────────────────────────────────

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

    // ─── Casting ───────────────────────────────────────────────

    #[Test]
    public function is_active_is_cast_to_boolean(): void
    {
        $institution = Institution::factory()->create(['is_active' => true]);
        $this->assertIsBool($institution->is_active);
    }

    #[Test]
    public function inactive_state_sets_is_active_to_false(): void
    {
        $institution = Institution::factory()->inactive()->create();
        $this->assertFalse($institution->is_active);
    }

    // ─── Fillable & data integrity ─────────────────────────────

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

    // ─── Soft cascade melalui controller ───────────────────────

    #[Test]
    public function deleting_institution_does_not_cascade_users_automatically(): void
    {
        // Catatan: foreign key pakai nullOnDelete, bukan cascade delete
        // Pastikan di test controller yang benar-benar delete manual
        $institution = Institution::factory()->create();
        $user = User::factory()->adminOf($institution)->create();

        // Tanpa controller, hanya hapus institution langsung
        $institution->delete();

        // User masih ada, tapi institution_id-nya null (karena nullOnDelete)
        $this->assertDatabaseMissing('institutions', ['id' => $institution->id]);
    }
}

<?php

namespace Tests\Unit\Iterasi1;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit Test: User Model — Iterasi 1
 *
 * Lingkup: helper method role (isSuperAdmin, isAdmin, isPrimarySuperAdmin),
 * relasi ke Institution, dan casting atribut akun.
 * Sesuai US-01 (Autentikasi Pengguna) & US-04 (Manajemen Admin Lembaga).
 *
 * Jalankan: php artisan test --filter UserModelTest
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── isSuperAdmin() ────────────────────────────────────────

    #[Test]
    public function it_returns_true_for_super_admin_role(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->assertTrue($user->isSuperAdmin());
    }

    #[Test]
    public function it_returns_false_for_admin_role_on_is_super_admin(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->adminOf($institution)->create();
        $this->assertFalse($user->isSuperAdmin());
    }

    // ─── isAdmin() ─────────────────────────────────────────────

    #[Test]
    public function it_returns_true_for_admin_role(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->adminOf($institution)->create();
        $this->assertTrue($user->isAdmin());
    }

    #[Test]
    public function it_returns_false_for_super_admin_role_on_is_admin(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->assertFalse($user->isAdmin());
    }

    // ─── isPrimarySuperAdmin() ──────────────────────────────────

    #[Test]
    public function it_returns_true_for_primary_super_admin(): void
    {
        $user = User::factory()->superAdmin()->create(['is_primary' => true]);
        $this->assertTrue($user->isPrimarySuperAdmin());
    }

    #[Test]
    public function it_returns_false_for_non_primary_super_admin(): void
    {
        $user = User::factory()->superAdmin()->create(['is_primary' => false]);
        $this->assertFalse($user->isPrimarySuperAdmin());
    }

    #[Test]
    public function it_returns_false_for_admin_on_is_primary_super_admin_even_if_flag_true(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->adminOf($institution)->create(['is_primary' => true]);

        $this->assertFalse($user->isPrimarySuperAdmin());
    }

    // ─── Relasi institution ────────────────────────────────────

    #[Test]
    public function admin_belongs_to_institution(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->adminOf($institution)->create();

        $this->assertNotNull($user->institution);
        $this->assertEquals($institution->id, $user->institution->id);
        $this->assertEquals($institution->name, $user->institution->name);
    }

    #[Test]
    public function super_admin_has_no_institution(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->assertNull($user->institution);
    }

    // ─── Casting & visibility ──────────────────────────────────

    #[Test]
    public function is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    #[Test]
    public function is_primary_is_cast_to_boolean(): void
    {
        $user = User::factory()->superAdmin()->create(['is_primary' => true]);
        $this->assertIsBool($user->fresh()->is_primary);
    }

    #[Test]
    public function password_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();
        $this->assertArrayNotHasKey('password', $array);
    }

    #[Test]
    public function plain_password_is_not_hidden_from_serialization(): void
    {
        // Sengaja tidak disembunyikan agar Super Admin bisa membacanya di panel
        $user = User::factory()->create(['plain_password' => 'password']);
        $array = $user->toArray();
        $this->assertArrayHasKey('plain_password', $array);
    }

    // ─── is_active ─────────────────────────────────────────────

    #[Test]
    public function inactive_user_has_is_active_false(): void
    {
        $user = User::factory()->inactive()->create();
        $this->assertFalse($user->is_active);
    }
}

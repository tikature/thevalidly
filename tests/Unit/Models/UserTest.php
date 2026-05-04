<?php

namespace Tests\Unit\Models;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit Test: User Model
 *
 * Menguji helper method dan relasi pada model User.
 * Jalankan: php artisan test --filter UserTest
 */
class UserTest extends TestCase
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

    // ─── Casting ───────────────────────────────────────────────

    #[Test]
    public function is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    #[Test]
    public function password_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();
        $this->assertArrayNotHasKey('password', $array);
    }

    // ─── is_active ─────────────────────────────────────────────

    #[Test]
    public function inactive_user_has_is_active_false(): void
    {
        $user = User::factory()->inactive()->create();
        $this->assertFalse($user->is_active);
    }
}

<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Super Admin Management
 *
 * Menguji:
 *  1. Super Admin bisa menambah Super Admin baru
 *  2. Super Admin bisa menghapus Super Admin lain
 *  3. Super Admin tidak bisa menghapus dirinya sendiri
 *  4. Validasi form tambah Super Admin
 *  5. plain_password tersimpan saat Super Admin baru dibuat
 *  6. Akses ditolak untuk non super_admin
 *
 * Jalankan: php artisan test --filter SuperAdminManagementTest
 */
class SuperAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $otherSuperAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'  => 'super_admin',
            'email' => 'superadmin@test.com',
        ]);

        $this->otherSuperAdmin = User::factory()->create([
            'role'  => 'super_admin',
            'email' => 'other.superadmin@test.com',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  1. TAMBAH SUPER ADMIN
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_add_new_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Super Admin Baru',
                'superadmin_email'    => 'newsuper@test.com',
                'superadmin_password' => 'password123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'newsuper@test.com',
            'role'  => 'super_admin',
        ]);
    }

    #[Test]
    public function new_super_admin_has_no_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Super Admin Baru',
                'superadmin_email'    => 'newsuper@test.com',
                'superadmin_password' => 'password123',
            ]);

        $this->assertDatabaseHas('users', [
            'email'          => 'newsuper@test.com',
            'role'           => 'super_admin',
            'institution_id' => null,
        ]);
    }

    #[Test]
    public function plain_password_is_stored_when_super_admin_is_created(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Super Admin Baru',
                'superadmin_email'    => 'newsuper@test.com',
                'superadmin_password' => 'password123',
            ]);

        $this->assertDatabaseHas('users', [
            'email'          => 'newsuper@test.com',
            'plain_password' => 'password123',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  2. HAPUS SUPER ADMIN
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_delete_other_super_admin(): void
    {
        $otherId = $this->otherSuperAdmin->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.superadmins.destroy', $this->otherSuperAdmin))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $otherId]);
    }

    #[Test]
    public function super_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.superadmins.destroy', $this->superAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Akun masih ada
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    // ══════════════════════════════════════════════════════════════
    //  3. VALIDASI FORM
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function superadmin_name_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => '',
                'superadmin_email'    => 'newsuper@test.com',
                'superadmin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['superadmin_name'], null, 'addSuperAdmin');
    }

    #[Test]
    public function superadmin_email_must_be_unique(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Duplikat',
                'superadmin_email'    => $this->otherSuperAdmin->email,
                'superadmin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['superadmin_email'], null, 'addSuperAdmin');
    }

    #[Test]
    public function superadmin_email_must_be_valid(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Test',
                'superadmin_email'    => 'bukan-email',
                'superadmin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['superadmin_email'], null, 'addSuperAdmin');
    }

    #[Test]
    public function superadmin_password_minimum_8_characters(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Test',
                'superadmin_email'    => 'newsuper@test.com',
                'superadmin_password' => '1234567',
            ])
            ->assertSessionHasErrors(['superadmin_password'], null, 'addSuperAdmin');
    }

    // ══════════════════════════════════════════════════════════════
    //  4. AKSES KONTROL
    // ══════════════════════════════════════════════════════════════

    #[Test]
    public function regular_admin_cannot_add_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Penyusup',
                'superadmin_email'    => 'penyusup@test.com',
                'superadmin_password' => 'password123',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_add_super_admin(): void
    {
        $this->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Guest',
                'superadmin_email'    => 'guest@test.com',
                'superadmin_password' => 'password123',
            ])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function regular_admin_cannot_delete_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('superadmin.superadmins.destroy', $this->otherSuperAdmin))
            ->assertForbidden();
    }
}
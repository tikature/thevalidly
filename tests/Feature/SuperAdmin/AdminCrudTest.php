<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Admin CRUD
 *
 * Fokus pada operasi tambah dan hapus akun admin per lembaga.
 * Jalankan: php artisan test --filter AdminCrudTest
 */
class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Institution $institution;
    private User $existingAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin    = User::factory()->superAdmin()->create();
        $this->institution   = Institution::factory()->create();
        $this->existingAdmin = User::factory()->adminOf($this->institution)->create();
    }

    // ─── CREATE ADMIN ──────────────────────────────────────────

    #[Test]
    public function super_admin_can_add_admin_to_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Baru',
                'admin_email'    => 'adminbaru@test.com',
                'admin_password' => 'password123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email'          => 'adminbaru@test.com',
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
    }

    #[Test]
    public function super_admin_can_add_multiple_admins_to_same_institution(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('superadmin.admins.store', $this->institution), [
            'admin_name'     => 'Admin Kedua',
            'admin_email'    => 'admin2@test.com',
            'admin_password' => 'password123',
        ]);

        $this->post(route('superadmin.admins.store', $this->institution), [
            'admin_name'     => 'Admin Ketiga',
            'admin_email'    => 'admin3@test.com',
            'admin_password' => 'password123',
        ]);

        $this->assertEquals(3, $this->institution->users()->count()); // 1 existing + 2 baru
    }

    #[Test]
    public function new_admin_password_is_hashed(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Hash',
                'admin_email'    => 'adminhash@test.com',
                'admin_password' => 'password123',
            ]);

        $admin = User::where('email', 'adminhash@test.com')->first();
        $this->assertNotEquals('password123', $admin->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $admin->password));
    }

    #[Test]
    public function new_admin_is_active_by_default(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Aktif',
                'admin_email'    => 'aktif@test.com',
                'admin_password' => 'password123',
            ]);

        $admin = User::where('email', 'aktif@test.com')->first();
        $this->assertTrue($admin->is_active);
    }

    #[Test]
    public function new_admin_role_is_always_admin_not_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Role',
                'admin_email'    => 'role@test.com',
                'admin_password' => 'password123',
            ]);

        $admin = User::where('email', 'role@test.com')->first();
        $this->assertEquals('admin', $admin->role);
        $this->assertFalse($admin->isSuperAdmin());
    }

    #[Test]
    public function new_admin_is_linked_to_correct_institution(): void
    {
        $otherInstitution = Institution::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $otherInstitution), [
                'admin_name'     => 'Admin Lembaga Lain',
                'admin_email'    => 'lain@test.com',
                'admin_password' => 'password123',
            ]);

        $admin = User::where('email', 'lain@test.com')->first();
        $this->assertEquals($otherInstitution->id, $admin->institution_id);
        $this->assertNotEquals($this->institution->id, $admin->institution_id);
    }

    // ─── VALIDASI CREATE ADMIN ─────────────────────────────────

    #[Test]
    public function admin_name_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => '',
                'admin_email'    => 'admin@test.com',
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors('admin_name');
    }

    #[Test]
    public function admin_email_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => '',
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    #[Test]
    public function admin_email_must_be_valid_format(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => 'bukan-email-valid',
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    #[Test]
    public function admin_email_must_be_unique(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Duplikat',
                'admin_email'    => $this->existingAdmin->email, // email sudah ada
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    #[Test]
    public function admin_password_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => 'admin@test.com',
                'admin_password' => '',
            ])
            ->assertSessionHasErrors('admin_password');
    }

    #[Test]
    public function admin_password_minimum_8_characters(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => 'admin@test.com',
                'admin_password' => 'abc1234', // 7 karakter
            ])
            ->assertSessionHasErrors('admin_password');
    }

    // ─── DELETE ADMIN ──────────────────────────────────────────

    #[Test]
    public function super_admin_can_delete_admin(): void
    {
        $adminId = $this->existingAdmin->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $this->existingAdmin))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $adminId]);
    }

    #[Test]
    public function deleting_admin_does_not_delete_institution(): void
    {
        $instId = $this->institution->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $this->existingAdmin));

        $this->assertDatabaseHas('institutions', ['id' => $instId]);
    }

    #[Test]
    public function deleting_one_admin_does_not_affect_other_admins(): void
    {
        $adminToKeep   = User::factory()->adminOf($this->institution)->create();
        $adminToDelete = User::factory()->adminOf($this->institution)->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $adminToDelete));

        $this->assertDatabaseHas('users', ['id' => $adminToKeep->id]);
    }

    #[Test]
    public function deleting_nonexistent_admin_returns_404(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', 99999))
            ->assertNotFound();
    }

    // ─── AKSES (proteksi role) ─────────────────────────────────

    #[Test]
    public function regular_admin_cannot_add_admin_to_any_institution(): void
    {
        $this->actingAs($this->existingAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Mau Tambah',
                'admin_email'    => 'tambah@test.com',
                'admin_password' => 'password123',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function regular_admin_cannot_delete_any_admin(): void
    {
        $otherAdmin = User::factory()->adminOf($this->institution)->create();

        $this->actingAs($this->existingAdmin)
            ->delete(route('superadmin.admins.destroy', $otherAdmin))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_add_admin(): void
    {
        $this->post(route('superadmin.admins.store', $this->institution), [
            'admin_name'     => 'Admin',
            'admin_email'    => 'admin@test.com',
            'admin_password' => 'password123',
        ])
        ->assertRedirect(route('login'));
    }
}

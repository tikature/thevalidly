<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: SuperAdmin — Kelola Lembaga & Admin
 *
 * Menguji semua operasi CRUD yang bisa dilakukan super admin.
 * Jalankan: php artisan test --filter SuperAdminTest
 */
class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularAdmin;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin   = User::factory()->superAdmin()->create();
        $this->institution  = Institution::factory()->create();
        $this->regularAdmin = User::factory()->adminOf($this->institution)->create();
    }

    // ─── Akses halaman ─────────────────────────────────────────

    #[Test]
    public function super_admin_can_access_superadmin_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'))
            ->assertStatus(200)
            ->assertSee('Panel Super Admin');
    }

    #[Test]
    public function guest_cannot_access_superadmin_index(): void
    {
        $this->get(route('superadmin.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_cannot_access_superadmin_index(): void
    {
        $this->actingAs($this->regularAdmin)
            ->get(route('superadmin.index'))
            ->assertForbidden();
    }

    // ─── Tambah Lembaga ────────────────────────────────────────

    #[Test]
    public function super_admin_can_create_institution_with_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Baru Test',
                'institution_email'   => 'lembaga@test.com',
                'institution_phone'   => '08123456789',
                'institution_address' => 'Jl. Test No. 1',
                'admin_name'          => 'Admin Lembaga',
                'admin_email'         => 'adminlembaga@test.com',
                'admin_password'      => 'password123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('institutions', ['name' => 'Lembaga Baru Test']);
        $this->assertDatabaseHas('users', [
            'email' => 'adminlembaga@test.com',
            'role'  => 'admin',
        ]);
    }

    #[Test]
    public function institution_name_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => '',
                'institution_email' => 'test@test.com',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@test.com',
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('institution_name');
    }

    #[Test]
    public function institution_email_must_be_unique(): void
    {
        Institution::factory()->create(['email' => 'existing@test.com']);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga Lain',
                'institution_email' => 'existing@test.com', // sudah ada
                'admin_name'        => 'Admin',
                'admin_email'       => 'newadmin@test.com',
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('institution_email');
    }

    #[Test]
    public function admin_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'existing@admin.com']);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga Baru',
                'institution_email' => 'baru@test.com',
                'admin_name'        => 'Admin Baru',
                'admin_email'       => 'existing@admin.com', // sudah ada
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    #[Test]
    public function admin_password_must_be_at_least_8_characters(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga Test',
                'institution_email' => 'test@test.com',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@test.com',
                'admin_password'    => 'short', // kurang dari 8
            ])
            ->assertSessionHasErrors('admin_password');
    }

    // ─── Toggle aktif/nonaktif lembaga ─────────────────────────

    #[Test]
    public function super_admin_can_deactivate_institution(): void
    {
        $this->assertTrue($this->institution->is_active);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($this->institution->fresh()->is_active);
    }

    #[Test]
    public function super_admin_can_reactivate_institution(): void
    {
        $inactive = Institution::factory()->inactive()->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inactive))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($inactive->fresh()->is_active);
    }

    #[Test]
    public function deactivating_institution_also_deactivates_its_admins(): void
    {
        $this->assertTrue($this->regularAdmin->is_active);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution));

        $this->assertFalse($this->regularAdmin->fresh()->is_active);
    }

    #[Test]
    public function reactivating_institution_also_reactivates_its_admins(): void
    {
        // Nonaktifkan dulu
        $this->institution->update(['is_active' => false]);
        $this->regularAdmin->update(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution));

        $this->assertTrue($this->regularAdmin->fresh()->is_active);
    }

    // ─── Hapus lembaga ─────────────────────────────────────────

    #[Test]
    public function super_admin_can_delete_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('institutions', ['id' => $this->institution->id]);
    }

    #[Test]
    public function deleting_institution_also_deletes_its_admins(): void
    {
        $adminId = $this->regularAdmin->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution));

        $this->assertDatabaseMissing('users', ['id' => $adminId]);
    }

    // ─── Tambah Admin ke Lembaga ───────────────────────────────

    #[Test]
    public function super_admin_can_add_admin_to_existing_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Kedua',
                'admin_email'    => 'admin2@test.com',
                'admin_password' => 'password123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email'          => 'admin2@test.com',
            'institution_id' => $this->institution->id,
            'role'           => 'admin',
        ]);
    }

    #[Test]
    public function new_admin_email_must_be_unique(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Duplikat',
                'admin_email'    => $this->regularAdmin->email, // email sudah ada
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    // ─── Hapus Admin ───────────────────────────────────────────

    #[Test]
    public function super_admin_can_delete_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $this->regularAdmin))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->regularAdmin->id]);
    }

    #[Test]
    public function admin_cannot_delete_other_admins(): void
    {
        $anotherAdmin = User::factory()->adminOf($this->institution)->create();

        $this->actingAs($this->regularAdmin)
            ->delete(route('superadmin.admins.destroy', $anotherAdmin))
            ->assertForbidden();
    }
}

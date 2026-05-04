<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Admin CRUD
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
        
        // Buat Super Admin (Pastikan role sesuai middleware: super_admin)
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin'
        ]);

        $this->institution = Institution::factory()->create();
        
        // Buat Admin existing untuk testing unique email
        $this->existingAdmin = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'email'          => 'existing_admin@test.com'
        ]);
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

    // ─── VALIDASI CREATE ADMIN (DENGAN ERROR BAGS) ──────────────

    #[Test]
    public function admin_name_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index')) // Harus ada agar back() berfungsi
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => '',
                'admin_email'    => 'admin@test.com',
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['admin_name'], null, 'addAdmin');
    }

    #[Test]
    public function admin_email_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => '',
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['admin_email'], null, 'addAdmin');
    }

    #[Test]
    public function admin_email_must_be_valid_format(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => 'bukan-email-valid',
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['admin_email'], null, 'addAdmin');
    }

    #[Test]
    public function admin_email_must_be_unique(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Duplikat',
                'admin_email'    => $this->existingAdmin->email,
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['admin_email'], null, 'addAdmin');
    }

    #[Test]
    public function admin_password_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => 'admin@test.com',
                'admin_password' => '',
            ])
            ->assertSessionHasErrors(['admin_password'], null, 'addAdmin');
    }

    #[Test]
    public function admin_password_minimum_8_characters(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin',
                'admin_email'    => 'admin@test.com',
                'admin_password' => '1234567', // 7 karakter
            ])
            ->assertSessionHasErrors(['admin_password'], null, 'addAdmin');
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
    public function regular_admin_cannot_add_admin_to_any_institution(): void
    {
        $this->actingAs($this->existingAdmin) // Login sebagai admin biasa
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Penyusup',
                'admin_email'    => 'penyusup@test.com',
                'admin_password' => 'password123',
            ])
            ->assertForbidden(); // Harus 403 karena middleware super_admin
    }

    #[Test]
    public function guest_cannot_add_admin(): void
    {
        $this->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Guest',
                'admin_email'    => 'guest@test.com',
                'admin_password' => 'password123',
            ])
            ->assertRedirect(route('login'));
    }
}
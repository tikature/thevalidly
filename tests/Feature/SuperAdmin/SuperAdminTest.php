<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: SuperAdmin — Kelola Lembaga & Admin
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
        
        // Buat Super Admin dengan role yang sesuai middleware (super_admin)
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin'
        ]);

        $this->institution = Institution::factory()->create(['is_active' => true]);
        
        $this->regularAdmin = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true
        ]);
    }

    // ─── Payload helper ────────────────────────────────────────

    private function institutionPayload(array $override = []): array
    {
        return array_merge([
            'institution_name'    => 'Lembaga Baru Test',
            'institution_email'   => 'lembaga@test.com',
            'institution_phone'   => '08123456789',
            'institution_address' => 'Jl. Test No. 1',
            'admin_name'          => 'Admin Lembaga',
            'admin_email'         => 'adminlembaga@test.com',
            'admin_password'      => 'password123',
        ], $override);
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
            ->post(route('superadmin.institutions.store'), $this->institutionPayload())
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
            ->from(route('superadmin.index')) // Simulasi asal agar back() bawa session
            ->post(route('superadmin.institutions.store'), $this->institutionPayload([
                'institution_name' => '',
            ]))
            ->assertSessionHasErrors(['institution_name'], null, 'addInstitution');
    }

    #[Test]
    public function institution_email_must_be_unique(): void
    {
        Institution::factory()->create(['email' => 'existing@test.com']);

        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.institutions.store'), $this->institutionPayload([
                'institution_email' => 'existing@test.com',
            ]))
            ->assertSessionHasErrors(['institution_email'], null, 'addInstitution');
    }

    #[Test]
    public function admin_password_must_be_at_least_8_characters(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.institutions.store'), $this->institutionPayload([
                'admin_password' => 'short',
            ]))
            ->assertSessionHasErrors(['admin_password'], null, 'addInstitution');
    }

    // ─── Toggle aktif/nonaktif lembaga ─────────────────────────

    #[Test]
    public function super_admin_can_deactivate_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution))
            ->assertRedirect();

        $this->assertFalse($this->institution->fresh()->is_active);
    }

    #[Test]
    public function deactivating_institution_also_deactivates_its_admins(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution));

        $this->assertFalse($this->regularAdmin->fresh()->is_active);
    }

    // ─── Hapus Lembaga ─────────────────────────────────────────

    #[Test]
    public function super_admin_can_delete_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution))
            ->assertRedirect();

        $this->assertDatabaseMissing('institutions', ['id' => $this->institution->id]);
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
            ->from(route('superadmin.index'))
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Duplikat',
                'admin_email'    => $this->regularAdmin->email,
                'admin_password' => 'password123',
            ])
            ->assertSessionHasErrors(['admin_email'], null, 'addAdmin');
    }

    // ─── Hapus Admin ───────────────────────────────────────────

    #[Test]
    public function super_admin_can_delete_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $this->regularAdmin))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $this->regularAdmin->id]);
    }
}
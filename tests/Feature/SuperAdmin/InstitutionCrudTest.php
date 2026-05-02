<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Institution CRUD
 * Jalankan: php artisan test --filter InstitutionCrudTest
 */
class InstitutionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat Super Admin dengan role yang sesuai middleware (super_admin)
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin'
        ]);
    }

    // ─── READ ──────────────────────────────────────────────────

    #[Test]
    public function index_shows_all_institutions(): void
    {
        Institution::factory()->count(3)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'))
            ->assertStatus(200)
            ->assertSee('Panel Super Admin');
    }

    #[Test]
    public function index_shows_empty_state_when_no_institutions(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'))
            ->assertStatus(200)
            ->assertSee('Belum ada lembaga terdaftar');
    }

    #[Test]
    public function index_shows_institution_name_and_email(): void
    {
        Institution::factory()->create([
            'name'  => 'Lembaga Tampil',
            'email' => 'tampil@lembaga.com',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'))
            ->assertSee('Lembaga Tampil')
            ->assertSee('tampil@lembaga.com');
    }

    // ─── CREATE ────────────────────────────────────────────────

    #[Test]
    public function super_admin_can_create_institution_with_all_fields(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Lengkap',
                'institution_email'   => 'lengkap@test.com',
                'institution_phone'   => '08111222333',
                'institution_address' => 'Jl. Lengkap No. 1, Purwokerto',
                'admin_name'          => 'Admin Pertama',
                'admin_email'         => 'admin@lengkap.com',
                'admin_password'      => 'rahasia123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('institutions', [
            'name'  => 'Lembaga Lengkap',
            'email' => 'lengkap@test.com',
        ]);
    }

    // ─── VALIDASI CREATE (DENGAN ERROR BAGS) ────────────────────

    #[Test]
    public function creating_institution_fails_without_phone(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index')) // Simulasi asal halaman
            ->post(route('superadmin.institutions.store'), $this->payload([
                'institution_phone' => '', 
            ]))
            ->assertSessionHasErrors(['institution_phone'], null, 'addInstitution');

        $this->assertDatabaseMissing('institutions', ['email' => 'valid@lembaga.com']);
    }

    #[Test]
    public function creating_institution_fails_without_address(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.institutions.store'), $this->payload([
                'institution_address' => '',
            ]))
            ->assertSessionHasErrors(['institution_address'], null, 'addInstitution');
    }

    #[Test]
    public function institution_name_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.institutions.store'), $this->payload([
                'institution_name' => '',
            ]))
            ->assertSessionHasErrors(['institution_name'], null, 'addInstitution');
    }

    #[Test]
    public function institution_email_must_be_unique(): void
    {
        Institution::factory()->create(['email' => 'duplikat@test.com']);

        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.institutions.store'), $this->payload([
                'institution_email' => 'duplikat@test.com',
            ]))
            ->assertSessionHasErrors(['institution_email'], null, 'addInstitution');
    }

    #[Test]
    public function admin_email_must_be_unique_across_users(): void
    {
        User::factory()->create(['email' => 'exists@user.com']);

        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->post(route('superadmin.institutions.store'), $this->payload([
                'admin_email' => 'exists@user.com',
            ]))
            ->assertSessionHasErrors(['admin_email'], null, 'addInstitution');
    }

    // ─── TOGGLE AKTIF / NONAKTIF ───────────────────────────────

    #[Test]
    public function super_admin_can_deactivate_active_institution(): void
    {
        $inst = Institution::factory()->create(['is_active' => true]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inst))
            ->assertRedirect();

        $this->assertFalse($inst->fresh()->is_active);
    }

    #[Test]
    public function deactivating_institution_deactivates_all_its_admins(): void
    {
        $inst   = Institution::factory()->create();
        $admin1 = User::factory()->create(['institution_id' => $inst->id, 'is_active' => true]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inst));

        $this->assertFalse($admin1->fresh()->is_active);
    }

    // ─── DELETE ────────────────────────────────────────────────

    #[Test]
    public function super_admin_can_delete_institution(): void
    {
        $inst = Institution::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $inst))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('institutions', ['id' => $inst->id]);
    }

    // ─── Payload helper ────────────────────────────────────────

    private function payload(array $override = []): array
    {
        return array_merge([
            'institution_name'    => 'Lembaga Valid',
            'institution_email'   => 'valid@lembaga.com',
            'institution_phone'   => '08111222333',
            'institution_address' => 'Jl. Valid No. 1',
            'admin_name'          => 'Admin Valid',
            'admin_email'         => 'admin@valid.com',
            'admin_password'      => 'password123',
        ], $override);
    }

    // ─── AKSES (proteksi role) ─────────────────────────────────

    #[Test]
    public function regular_admin_cannot_create_institution(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('superadmin.institutions.store'), [])
            ->assertForbidden();
    }
}
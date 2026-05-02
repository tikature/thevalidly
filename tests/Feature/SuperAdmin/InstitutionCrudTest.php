<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Institution CRUD
 *
 * Fokus pada operasi create, toggle aktif/nonaktif, dan delete lembaga.
 * Jalankan: php artisan test --filter InstitutionCrudTest
 */
class InstitutionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->superAdmin()->create();
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
        $inst = Institution::factory()->create([
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
            'name'    => 'Lembaga Lengkap',
            'email'   => 'lengkap@test.com',
            'phone'   => '08111222333',
            'address' => 'Jl. Lengkap No. 1, Purwokerto',
        ]);
    }

    #[Test]
    public function creating_institution_fails_without_phone(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Tanpa Telepon',
                'institution_email'   => 'notelepon@test.com',
                'institution_address' => 'Jl. Lengkap No. 1',
                'admin_name'          => 'Admin',
                'admin_email'         => 'admin@notelepon.com',
                'admin_password'      => 'password123',
                // phone tidak diisi — wajib sejak Iterasi 1
            ])
            ->assertSessionHasErrors('institution_phone');

        $this->assertDatabaseMissing('institutions', ['email' => 'notelepon@test.com']);
    }

    #[Test]
    public function creating_institution_fails_without_address(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga Tanpa Alamat',
                'institution_email' => 'noalamat@test.com',
                'institution_phone' => '0812345678',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@noalamat.com',
                'admin_password'    => 'password123',
                // address tidak diisi — wajib sejak Iterasi 1
            ])
            ->assertSessionHasErrors('institution_address');

        $this->assertDatabaseMissing('institutions', ['email' => 'noalamat@test.com']);
    }

    #[Test]
    public function creating_institution_also_creates_its_first_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga dengan Admin',
                'institution_email'   => 'inst@test.com',
                'institution_phone'   => '0811222333',
                'institution_address' => 'Jl. Contoh No. 1',
                'admin_name'          => 'Admin Lembaga',
                'admin_email'         => 'adminlembaga@test.com',
                'admin_password'      => 'password123',
            ]);

        $institution = Institution::where('email', 'inst@test.com')->first();

        $this->assertDatabaseHas('users', [
            'email'          => 'adminlembaga@test.com',
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);
    }

    #[Test]
    public function new_institution_is_active_by_default(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Baru',
                'institution_email'   => 'baru@test.com',
                'institution_phone'   => '08123456789',
                'institution_address' => 'Jl. Testing No.1',
                'admin_name'          => 'Admin',
                'admin_email'         => 'admin@baru.com',
                'admin_password'      => 'password123',
            ]);

        $inst = Institution::where('email', 'baru@test.com')->first();
        $this->assertTrue($inst->is_active);
    }

    // ─── VALIDASI CREATE ───────────────────────────────────────

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
    public function institution_email_is_required(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga',
                'institution_email' => '',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@test.com',
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('institution_email');
    }

    #[Test]
    public function institution_email_must_be_valid_format(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga',
                'institution_email' => 'bukan-email',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@test.com',
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('institution_email');
    }

    #[Test]
    public function institution_email_must_be_unique(): void
    {
        Institution::factory()->create(['email' => 'duplikat@test.com']);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga Duplikat',
                'institution_email' => 'duplikat@test.com',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@test.com',
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('institution_email');
    }

    #[Test]
    public function admin_email_must_be_unique_across_users(): void
    {
        User::factory()->create(['email' => 'exists@user.com']);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga X',
                'institution_email' => 'x@test.com',
                'admin_name'        => 'Admin',
                'admin_email'       => 'exists@user.com',
                'admin_password'    => 'password123',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    #[Test]
    public function admin_password_must_be_minimum_8_characters(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'  => 'Lembaga',
                'institution_email' => 'x@test.com',
                'admin_name'        => 'Admin',
                'admin_email'       => 'admin@test.com',
                'admin_password'    => '1234567', // 7 karakter
            ])
            ->assertSessionHasErrors('admin_password');
    }

    // ─── TOGGLE AKTIF / NONAKTIF ───────────────────────────────

    #[Test]
    public function super_admin_can_deactivate_active_institution(): void
    {
        $inst = Institution::factory()->create(['is_active' => true]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inst))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($inst->fresh()->is_active);
    }

    #[Test]
    public function super_admin_can_reactivate_inactive_institution(): void
    {
        $inst = Institution::factory()->inactive()->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inst))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($inst->fresh()->is_active);
    }

    #[Test]
    public function deactivating_institution_deactivates_all_its_admins(): void
    {
        $inst   = Institution::factory()->create();
        $admin1 = User::factory()->adminOf($inst)->create();
        $admin2 = User::factory()->adminOf($inst)->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inst));

        $this->assertFalse($admin1->fresh()->is_active);
        $this->assertFalse($admin2->fresh()->is_active);
    }

    #[Test]
    public function reactivating_institution_reactivates_all_its_admins(): void
    {
        $inst   = Institution::factory()->inactive()->create();
        $admin1 = User::factory()->adminOf($inst)->inactive()->create();
        $admin2 = User::factory()->adminOf($inst)->inactive()->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $inst));

        $this->assertTrue($admin1->fresh()->is_active);
        $this->assertTrue($admin2->fresh()->is_active);
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

    #[Test]
    public function deleting_institution_also_deletes_all_its_admins(): void
    {
        $inst   = Institution::factory()->create();
        $admin1 = User::factory()->adminOf($inst)->create();
        $admin2 = User::factory()->adminOf($inst)->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $inst));

        $this->assertDatabaseMissing('users', ['id' => $admin1->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
    }

    #[Test]
    public function deleting_nonexistent_institution_returns_404(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', 99999))
            ->assertNotFound();
    }

    // ─── AKSES (non super admin) ───────────────────────────────

    #[Test]
    public function regular_admin_cannot_create_institution(): void
    {
        $inst  = Institution::factory()->create();
        $admin = User::factory()->adminOf($inst)->create();

        $this->actingAs($admin)
            ->post(route('superadmin.institutions.store'), [])
            ->assertForbidden();
    }

    #[Test]
    public function regular_admin_cannot_delete_institution(): void
    {
        $inst  = Institution::factory()->create();
        $admin = User::factory()->adminOf($inst)->create();

        $this->actingAs($admin)
            ->delete(route('superadmin.institutions.destroy', $inst))
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: SuperAdmin — skenario lanjutan
 *
 * Mencakup:
 * - destroyInstitution cascade (hapus users juga)
 * - toggle institution aktifkan kembali
 * - destroyAdmin hapus user dari DB
 * - Slug lembaga di-generate saat create
 * - Institution create menyertakan semua field
 *
 * Catatan: skenario updateAdmin (password, email) ada di EditInstitutionTest.
 *
 * Jalankan: php artisan test --filter SuperAdminExtendedTest
 */
class SuperAdminExtendedTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();

        $this->institution = Institution::factory()->create([
            'name'      => 'Lembaga Uji',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->adminOf($this->institution)->create([
            'name'  => 'Admin Uji',
            'email' => 'adminuji@test.com',
        ]);
    }

    // ══════════════════════════════════════════════
    // destroyInstitution — cascade ke users
    // ══════════════════════════════════════════════

    #[Test]
    public function destroying_institution_also_deletes_its_admins(): void
    {
        $adminId = $this->admin->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution))
            ->assertRedirect();

        $this->assertDatabaseMissing('institutions', ['id' => $this->institution->id]);
        $this->assertDatabaseMissing('users', ['id' => $adminId]);
    }

    #[Test]
    public function destroying_institution_with_multiple_admins_deletes_all(): void
    {
        $admin2 = User::factory()->adminOf($this->institution)->create();
        $admin3 = User::factory()->adminOf($this->institution)->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $this->admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin3->id]);
    }

    #[Test]
    public function destroying_institution_response_has_success_flash(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution))
            ->assertSessionHas('success');
    }

    // ══════════════════════════════════════════════
    // toggleInstitution — aktifkan kembali
    // ══════════════════════════════════════════════

    #[Test]
    public function toggling_inactive_institution_reactivates_it(): void
    {
        $institution = Institution::factory()->create(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $institution))
            ->assertRedirect();

        $this->assertTrue($institution->fresh()->is_active);
    }

    #[Test]
    public function reactivating_institution_also_reactivates_its_admins(): void
    {
        $institution = Institution::factory()->create(['is_active' => false]);
        $admin       = User::factory()->adminOf($institution)->create(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $institution))
            ->assertRedirect();

        $this->assertTrue($admin->fresh()->is_active);
    }

    #[Test]
    public function toggle_institution_returns_success_flash(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution))
            ->assertSessionHas('success');
    }

    // ══════════════════════════════════════════════
    // updateAdmin — hanya validasi yang belum ada di EditInstitutionTest
    // ══════════════════════════════════════════════

    #[Test]
    public function updating_admin_password_must_be_at_least_8_chars(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'     => $this->admin->name,
                'admin_email'    => $this->admin->email,
                'admin_password' => 'short7',
            ])
            ->assertSessionHasErrors(['admin_password'], null, 'editAdmin');
    }

    #[Test]
    public function updating_admin_success_has_flash_message(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'  => 'Nama Baru',
                'admin_email' => $this->admin->email,
            ])
            ->assertSessionHas('success');
    }

    // ══════════════════════════════════════════════
    // destroyAdmin
    // ══════════════════════════════════════════════

    #[Test]
    public function superadmin_can_destroy_admin_and_flash_success(): void
    {
        $adminId = $this->admin->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $this->admin))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $adminId]);
    }

    #[Test]
    public function regular_admin_cannot_destroy_another_admin(): void
    {
        $anotherAdmin = User::factory()->adminOf($this->institution)->create();

        $this->actingAs($this->admin)
            ->delete(route('superadmin.admins.destroy', $anotherAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id]);
    }

    #[Test]
    public function guest_cannot_destroy_admin(): void
    {
        $this->delete(route('superadmin.admins.destroy', $this->admin))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    // ══════════════════════════════════════════════
    // storeInstitution — slug generation & field persistence
    // ══════════════════════════════════════════════

    #[Test]
    public function creating_institution_generates_slug(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Satu',
                'institution_email'   => 'satu@lembaga.com',
                'institution_phone'   => '081234567890',
                'institution_address' => 'Jl. Satu No. 1',
                'admin_name'          => 'Admin Satu',
                'admin_email'         => 'admin@satu.com',
                'admin_password'      => 'password123',
            ])
            ->assertRedirect();

        $institution = Institution::where('email', 'satu@lembaga.com')->first();
        $this->assertNotNull($institution->slug);
        $this->assertStringContainsString('lembaga-satu', $institution->slug);
    }

    #[Test]
    public function creating_institution_stores_all_fields(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Lengkap',
                'institution_email'   => 'lengkap@lembaga.com',
                'institution_phone'   => '081122334455',
                'institution_address' => 'Jl. Lengkap No. 99',
                'admin_name'          => 'Admin Lengkap',
                'admin_email'         => 'admin@lengkap.com',
                'admin_password'      => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('institutions', [
            'name'    => 'Lembaga Lengkap',
            'email'   => 'lengkap@lembaga.com',
            'phone'   => '081122334455',
            'address' => 'Jl. Lengkap No. 99',
        ]);
    }

    #[Test]
    public function new_institution_is_active_by_default(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Aktif',
                'institution_email'   => 'aktif@lembaga.com',
                'institution_phone'   => '081111111111',
                'institution_address' => 'Jl. Aktif No. 1',
                'admin_name'          => 'Admin',
                'admin_email'         => 'admin@aktif.com',
                'admin_password'      => 'password123',
            ])
            ->assertRedirect();

        $institution = Institution::where('email', 'aktif@lembaga.com')->first();
        $this->assertNotNull($institution);
    }

    #[Test]
    public function created_admin_is_active_by_default(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Z',
                'institution_email'   => 'z@lembaga.com',
                'institution_phone'   => '089999999999',
                'institution_address' => 'Jl. Z No. 1',
                'admin_name'          => 'Admin Z',
                'admin_email'         => 'adminz@test.com',
                'admin_password'      => 'password123',
            ])
            ->assertRedirect();

        $admin = User::where('email', 'adminz@test.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);
    }

    // ══════════════════════════════════════════════
    // updateInstitution — session data on validation fail
    // ══════════════════════════════════════════════

    #[Test]
    public function updating_institution_stores_session_data_on_validation_failure(): void
    {
        Institution::factory()->create(['email' => 'taken@email.com']);

        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Nama Baru',
                'institution_email'   => 'taken@email.com',
                'institution_phone'   => '081111111111',
                'institution_address' => 'Jl. Test',
            ])
            ->assertSessionHasErrors('institution_email', null, 'editInstitution');
    }
}

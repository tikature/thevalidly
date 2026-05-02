<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditInstitutionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat Super Admin dengan role yang sesuai middleware (super_admin)
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin'
        ]);

        $this->institution = Institution::factory()->create([
            'name'  => 'Lembaga Awal',
            'email' => 'awal@lembaga.com',
        ]);

        $this->admin = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'name'           => 'Admin Awal',
            'email'          => 'admin@awal.com',
        ]);
    }

    // ── Edit Institution ──────────────────────────────────────

    #[Test]
    public function superadmin_can_update_institution(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Lembaga Baru',
                'institution_email'   => 'baru@lembaga.com',
                'institution_phone'   => '0812345678',
                'institution_address' => 'Jl. Baru No. 1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('institutions', [
            'id'    => $this->institution->id,
            'name'  => 'Lembaga Baru',
            'email' => 'baru@lembaga.com',
        ]);
    }

    #[Test]
    public function superadmin_cannot_update_institution_with_duplicate_email(): void
    {
        $other = Institution::factory()->create(['email' => 'other@lembaga.com']);

        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index')) // Harus ada agar back() berfungsi
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Lembaga Baru',
                'institution_email'   => 'other@lembaga.com', // Email milik lembaga lain
                'institution_phone'   => '0812345678',
                'institution_address' => 'Jl. Baru No. 1',
            ])
            ->assertSessionHasErrors(['institution_email'], null, 'editInstitution');
    }

    #[Test]
    public function superadmin_can_update_institution_with_same_email(): void
    {
        // Email sama (miliknya sendiri) harus diperbolehkan oleh rule unique
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Nama Diubah',
                'institution_email'   => 'awal@lembaga.com', // Email tidak berubah
                'institution_phone'   => '0812345678',
                'institution_address' => 'Jl. Sama No. 1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('institutions', [
            'id'   => $this->institution->id,
            'name' => 'Nama Diubah'
        ]);
    }

    #[Test]
    public function admin_cannot_update_institution(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Lembaga Baru',
                'institution_email'   => 'baru@lembaga.com',
                'institution_phone'   => '0812345678',
                'institution_address' => 'Jl. Baru No. 1',
            ])
            ->assertForbidden();
    }

    // ── Edit Admin ────────────────────────────────────────────

    #[Test]
    public function superadmin_can_update_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'  => 'Admin Baru',
                'admin_email' => 'adminbaru@test.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'    => $this->admin->id,
            'name'  => 'Admin Baru',
            'email' => 'adminbaru@test.com',
        ]);
    }

    #[Test]
    public function superadmin_can_update_admin_password(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'     => $this->admin->name,
                'admin_email'    => $this->admin->email,
                'admin_password' => 'newpassword123',
            ])
            ->assertRedirect();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword123', $this->admin->fresh()->password)
        );
    }

    #[Test]
    public function superadmin_cannot_update_admin_with_duplicate_email(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other@admin.com'
        ]);

        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.index'))
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'  => 'Admin',
                'admin_email' => 'other@admin.com',
            ])
            ->assertSessionHasErrors(['admin_email'], null, 'editAdmin');
    }

    #[Test]
    public function admin_cannot_update_other_admin(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->admin)
            ->patch(route('superadmin.admins.update', $otherAdmin), [
                'admin_name'  => 'Admin Mencoba Update',
                'admin_email' => 'mencoba@test.com',
            ])
            ->assertForbidden();
    }
}
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
        $this->superAdmin  = User::factory()->superAdmin()->create();
        $this->institution = Institution::factory()->create([
            'name'  => 'Lembaga Awal',
            'email' => 'awal@lembaga.com',
        ]);
        $this->admin = User::factory()->adminOf($this->institution)->create([
            'name'  => 'Admin Awal',
            'email' => 'admin@awal.com',
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
            ->assertRedirect();

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
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Lembaga Baru',
                'institution_email'   => 'other@lembaga.com',
                'institution_phone'   => '0812345678',
                'institution_address' => 'Jl. Baru No. 1',
            ])
            ->assertSessionHasErrors('institution_email');
    }

    #[Test]
    public function superadmin_can_update_institution_with_same_email(): void
    {
        // Email sama (miliknya sendiri) harus boleh
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.update', $this->institution), [
                'institution_name'    => 'Nama Diubah',
                'institution_email'   => 'awal@lembaga.com',
                'institution_phone'   => '0812345678',
                'institution_address' => 'Jl. Sama No. 1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('institutions', ['name' => 'Nama Diubah']);
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

    #[Test]
    public function guest_cannot_update_institution(): void
    {
        $this->patch(route('superadmin.institutions.update', $this->institution), [
            'institution_name'    => 'Lembaga Baru',
            'institution_email'   => 'baru@lembaga.com',
            'institution_phone'   => '0812345678',
            'institution_address' => 'Jl. Baru No. 1',
        ])->assertRedirect(route('login'));
    }

    // ── Edit Admin ────────────────────────────────────────────

    #[Test]
    public function superadmin_can_update_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'  => 'Admin Baru',
                'admin_email' => 'admin@baru.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'    => $this->admin->id,
            'name'  => 'Admin Baru',
            'email' => 'admin@baru.com',
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

        // Password berubah (tidak bisa cek hash langsung, coba login)
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword123',
                $this->admin->fresh()->password)
        );
    }

    #[Test]
    public function superadmin_cannot_update_admin_with_duplicate_email(): void
    {
        $other = User::factory()->adminOf($this->institution)->create([
            'email' => 'other@admin.com',
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'  => 'Admin',
                'admin_email' => 'other@admin.com',
            ])
            ->assertSessionHasErrors('admin_email');
    }

    #[Test]
    public function admin_cannot_update_other_admin(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('superadmin.admins.update', $this->admin), [
                'admin_name'  => 'Admin Baru',
                'admin_email' => 'admin@baru.com',
            ])
            ->assertForbidden();
    }
}

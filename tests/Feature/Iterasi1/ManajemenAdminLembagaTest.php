<?php

namespace Tests\Feature\Iterasi1;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-004 — Manajemen Admin Lembaga
 * Iterasi 1 | US04
 *
 * Jumlah test method: 7 (sesuai jumlah AC)
 */
class ManajemenAdminLembagaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'       => 'super_admin',
            'is_active'  => true,
            'is_primary' => true,
        ]);

        $this->institution = Institution::factory()->create(['is_active' => true]);
    }

    /**
     * AC1: Super Admin dapat menambah akun admin baru pada lembaga
     * yang sudah terdaftar.
     */
    public function test_super_admin_dapat_menambah_admin_baru_ke_lembaga(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Baru',
                'admin_email'    => 'adminbaru@expertindo.com',
                'admin_password' => 'password123',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email'          => 'adminbaru@expertindo.com',
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * AC2: Ketika email admin yang didaftarkan sudah digunakan akun lain
     * di sistem, Super Admin mendapat pesan peringatan dan akun tidak tersimpan.
     */
    public function test_tambah_admin_ditolak_jika_email_sudah_digunakan(): void
    {
        User::factory()->create(['email' => 'duplikat@admin.com']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.admins.store', $this->institution), [
                'admin_name'     => 'Admin Duplikat',
                'admin_email'    => 'duplikat@admin.com',
                'admin_password' => 'password123',
            ]);

        $response->assertSessionHasErrors(['admin_email'], null, 'addAdmin');
    }

    /**
     * AC3: Super Admin dapat mengubah nama dan email admin yang sudah ada.
     */
    public function test_super_admin_dapat_mengubah_nama_dan_email_admin(): void
    {
        $admin = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.admins.update', $admin), [
                'admin_name'  => 'Nama Diubah',
                'admin_email' => 'emailbaru@expertindo.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id'    => $admin->id,
            'name'  => 'Nama Diubah',
            'email' => 'emailbaru@expertindo.com',
        ]);
    }

    /**
     * AC4: Ketika Super Admin menghapus akun admin, akun tersebut
     * tidak dapat digunakan untuk login.
     */
    public function test_admin_yang_dihapus_tidak_dapat_login(): void
    {
        $admin = User::factory()->create([
            'role'           => 'admin',
            'password'       => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.admins.destroy', $admin));

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);

        $this->post(route('login.post'), [
            'email'    => $admin->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    /**
     * AC5: Ketika Super Admin menonaktifkan sebuah lembaga, seluruh akun
     * admin di lembaga tersebut ikut dinonaktifkan secara otomatis dan tidak dapat login.
     */
    public function test_nonaktifkan_lembaga_menonaktifkan_seluruh_admin_secara_otomatis(): void
    {
        $admin = User::factory()->create([
            'role'           => 'admin',
            'password'       => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution));

        $this->assertDatabaseHas('users', [
            'id'        => $admin->id,
            'is_active' => false,
        ]);

        $this->post(route('login.post'), [
            'email'    => $admin->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    /**
     * AC6: Ketika lembaga yang sebelumnya dinonaktifkan diaktifkan kembali,
     * seluruh admin lembaga tersebut dapat login kembali seperti semula.
     */
    public function test_aktifkan_kembali_lembaga_memungkinkan_admin_login_kembali(): void
    {
        $this->institution->update(['is_active' => false]);

        $admin = User::factory()->create([
            'role'           => 'admin',
            'password'       => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active'      => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.institutions.toggle', $this->institution));

        $this->assertDatabaseHas('users', [
            'id'        => $admin->id,
            'is_active' => true,
        ]);

        $this->post(route('login.post'), [
            'email'    => $admin->email,
            'password' => 'password123',
        ])->assertRedirect(route('certificate.index'));
    }

    /**
     * AC7: Ketika Super Admin menghapus sebuah lembaga, seluruh akun
     * admin yang terdaftar di lembaga tersebut ikut terhapus.
     */
    public function test_hapus_lembaga_menghapus_seluruh_admin_terkait(): void
    {
        $admin = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.institutions.destroy', $this->institution));

        $this->assertDatabaseMissing('institutions', ['id' => $this->institution->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }
}
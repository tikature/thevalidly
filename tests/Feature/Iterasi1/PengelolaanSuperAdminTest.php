<?php

namespace Tests\Feature\Iterasi1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-005 — Pengelolaan Super Admin
 * Iterasi 1 | US05
 *
 * Jumlah test method: 6 (sesuai jumlah AC)
 */
class PengelolaanSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdminUtama;
    private User $superAdminLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminUtama = User::factory()->create([
            'role'       => 'super_admin',
            'is_active'  => true,
            'is_primary' => true,
        ]);

        $this->superAdminLain = User::factory()->create([
            'role'       => 'super_admin',
            'is_active'  => true,
            'is_primary' => false,
        ]);
    }

    /**
     * AC1: Super Admin dapat membuat akun Super Admin baru yang dapat
     * langsung digunakan untuk login ke sistem.
     */
    public function test_super_admin_dapat_membuat_akun_super_admin_baru_dan_langsung_login(): void
    {
        $response = $this->actingAs($this->superAdminUtama)
            ->post(route('superadmin.superadmins.store'), [
                'superadmin_name'     => 'Super Admin Baru',
                'superadmin_email'    => 'superadminbaru@validly.id',
                'superadmin_password' => 'password123',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'superadminbaru@validly.id',
            'role'  => 'super_admin',
        ]);

        $this->post(route('login.post'), [
            'email'    => 'superadminbaru@validly.id',
            'password' => 'password123',
        ])->assertRedirect(route('superadmin.index'));
    }

    /**
     * AC2: Daftar seluruh akun Super Admin yang terdaftar dapat dilihat
     * beserta nama, email, dan statusnya.
     */
    public function test_daftar_seluruh_super_admin_dapat_dilihat(): void
    {
        $response = $this->actingAs($this->superAdminUtama)
            ->get(route('superadmin.index'));

        $response->assertOk();
        $response->assertViewHas('superAdmins', function ($superAdmins) {
            return $superAdmins->contains('id', $this->superAdminUtama->id)
                && $superAdmins->contains('id', $this->superAdminLain->id);
        });
    }

    /**
     * AC3: Akun Super Admin pertama (utama) ditandai dengan penanda khusus
     * yang membedakannya dari akun lain di daftar.
     */
    public function test_akun_super_admin_utama_ditandai_is_primary_true(): void
    {
        $response = $this->actingAs($this->superAdminUtama)
            ->get(route('superadmin.index'));

        $response->assertOk();
        $response->assertViewHas('superAdmins', function ($superAdmins) {
            $utama = $superAdmins->firstWhere('id', $this->superAdminUtama->id);
            return $utama && $utama->is_primary === true;
        });
    }

    /**
     * AC4: Super Admin dapat menghapus akun Super Admin lain sehingga
     * akun tersebut tidak dapat login.
     */
    public function test_super_admin_dapat_menghapus_akun_super_admin_lain(): void
    {
        $response = $this->actingAs($this->superAdminUtama)
            ->delete(route('superadmin.superadmins.destroy', $this->superAdminLain));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $this->superAdminLain->id]);
    }

    /**
     * AC5: Super Admin tidak dapat menonaktifkan atau menghapus akunnya
     * sendiri yang sedang digunakan.
     */
    public function test_super_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $response = $this->actingAs($this->superAdminUtama)
            ->delete(route('superadmin.superadmins.destroy', $this->superAdminUtama));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdminUtama->id]);
    }

    /**
     * AC6: Akun Super Admin pertama yang terdaftar di sistem ditandai sebagai
     * akun utama dan tidak dapat dihapus atau dinonaktifkan oleh siapapun.
     */
    public function test_akun_super_admin_utama_tidak_dapat_dihapus_oleh_siapapun(): void
    {
        $response = $this->actingAs($this->superAdminLain)
            ->delete(route('superadmin.superadmins.destroy', $this->superAdminUtama));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdminUtama->id]);
    }
}
<?php

namespace Tests\Feature\Iterasi1;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-007 — Pengelolaan Profil Admin
 * Iterasi 1 | US07
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class PengelolaanProfilAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $adminLembaga;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $institution = Institution::factory()->create(['is_active' => true]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'name'           => 'Ashley Hardy',
            'email'          => 'ashley@expertindo.id',
            'password'       => bcrypt('password123'),
            'institution_id' => $institution->id,
            'is_active'      => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role'       => 'super_admin',
            'is_active'  => true,
            'is_primary' => true,
        ]);
    }

    /**
     * AC1: Halaman profil menampilkan nama dan email akun Admin Lembaga
     * yang sedang login.
     */
    public function test_halaman_profil_menampilkan_nama_dan_email_admin_yang_login(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewHas('user', function ($user) {
            return $user->id === $this->adminLembaga->id
                && $user->name === 'Ashley Hardy'
                && $user->email === 'ashley@expertindo.id';
        });
    }

    /**
     * AC2: Admin Lembaga dapat memperbarui nama dan email;
     * perubahan langsung tampil setelah disimpan.
     */
    public function test_admin_lembaga_dapat_memperbarui_nama_dan_email(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->patch(route('profile.update'), [
                'name'  => 'Ashley Hardy Updated',
                'email' => 'ashley.new@expertindo.id',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id'    => $this->adminLembaga->id,
            'name'  => 'Ashley Hardy Updated',
            'email' => 'ashley.new@expertindo.id',
        ]);
    }

    /**
     * AC3: Admin Lembaga dapat mengganti password melalui halaman profil;
     * setelah berhasil, password baru dapat digunakan untuk login.
     */
    public function test_admin_lembaga_dapat_mengganti_password_dan_login_dengan_password_baru(): void
    {
        $this->actingAs($this->adminLembaga)
            ->patch(route('profile.update'), [
                'name'                  => $this->adminLembaga->name,
                'email'                 => $this->adminLembaga->email,
                'password'              => 'passwordbaru123',
                'password_confirmation' => 'passwordbaru123',
            ]);

        $this->post(route('logout'));

        $this->post(route('login.post'), [
            'email'    => $this->adminLembaga->email,
            'password' => 'passwordbaru123',
        ])->assertRedirect(route('certificate.index'));
    }

    /**
     * AC4: Ketika field password dibiarkan kosong saat menyimpan profil,
     * password yang ada tidak berubah.
     */
    public function test_password_tidak_berubah_jika_field_password_dikosongkan(): void
    {
        $this->actingAs($this->adminLembaga)
            ->patch(route('profile.update'), [
                'name'     => 'Ashley Hardy',
                'email'    => 'ashley@expertindo.id',
                'password' => '',
            ]);

        $this->post(route('logout'));

        $this->post(route('login.post'), [
            'email'    => 'ashley@expertindo.id',
            'password' => 'password123',
        ])->assertRedirect(route('certificate.index'));
    }

    /**
     * AC5: Halaman profil hanya dapat diakses oleh Admin Lembaga;
     * Super Admin tidak dapat mengakses halaman ini.
     */
    public function test_super_admin_tidak_dapat_mengakses_halaman_profil_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('profile.edit'));

        $response->assertForbidden();
    }
}




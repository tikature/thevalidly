<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-001 — Autentikasi Pengguna
 * Iterasi 1 | US01
 *
 * Jumlah test method: 8 (sesuai jumlah AC)
 */
class AutentikasiPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adminLembaga;
    private User $adminNonaktif;

    protected function setUp(): void
    {
        parent::setUp();

        $institution = Institution::factory()->create(['is_active' => true]);

        $this->superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'password'  => bcrypt('password123'),
            'is_active' => true,
            'is_primary'=> true,
        ]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'password'       => bcrypt('password123'),
            'institution_id' => $institution->id,
            'is_active'      => true,
        ]);

        $this->adminNonaktif = User::factory()->create([
            'role'           => 'admin',
            'password'       => bcrypt('password123'),
            'institution_id' => $institution->id,
            'is_active'      => false,
        ]);
    }

    /**
     * AC1: Pengguna yang belum login dan mencoba mengakses halaman
     * manapun di sistem diarahkan ke halaman login terlebih dahulu.
     */
    public function test_pengguna_belum_login_diarahkan_ke_halaman_login(): void
    {
        $response = $this->get(route('certificate.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * AC2: Ketika pengguna memasukkan email dan password yang benar,
     * pengguna berhasil masuk ke sistem dan diarahkan ke halaman
     * yang sesuai dengan perannya.
     */
    public function test_login_email_password_benar_pengguna_berhasil_masuk(): void
    {
        $response = $this->post(route('login.post'), [
            'email'    => $this->adminLembaga->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($this->adminLembaga);
        $response->assertRedirect(route('certificate.index'));
    }

    /**
     * AC3: Ketika Super Admin berhasil login, ia diarahkan ke panel
     * manajemen Lembaga.
     */
    public function test_super_admin_berhasil_login_diarahkan_ke_panel_superadmin(): void
    {
        $response = $this->post(route('login.post'), [
            'email'    => $this->superAdmin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('superadmin.index'));
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    /**
     * AC4: Ketika Admin Lembaga berhasil login, ia diarahkan ke
     * halaman generator sertifikat.
     */
    public function test_admin_lembaga_berhasil_login_diarahkan_ke_generator(): void
    {
        $response = $this->post(route('login.post'), [
            'email'    => $this->adminLembaga->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('certificate.index'));
        $this->assertAuthenticatedAs($this->adminLembaga);
    }

    /**
     * AC5: Ketika pengguna memasukkan email atau password yang salah,
     * sistem menampilkan pesan error dan pengguna tetap di halaman login.
     */
    public function test_login_email_atau_password_salah_menampilkan_error(): void
    {
        $response = $this->post(route('login.post'), [
            'email'    => $this->adminLembaga->email,
            'password' => 'passwordSalah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * AC6: Ketika akun yang digunakan untuk login telah dinonaktifkan,
     * sistem menolak akses dan menampilkan pesan yang menjelaskan kondisi tersebut.
     */
    public function test_login_akun_nonaktif_ditolak_dengan_pesan_informatif(): void
    {
        $response = $this->post(route('login.post'), [
            'email'    => $this->adminNonaktif->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * AC7: Pengguna yang sudah login tidak dapat mengakses halaman
     * yang diperuntukkan bagi role lain.
     */
    public function test_admin_lembaga_tidak_bisa_akses_halaman_role_superadmin(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->get(route('superadmin.index'));

        $response->assertForbidden();
    }

    /**
     * AC8: Ketika pengguna mengklik Logout, sesi dihapus dan pengguna
     * tidak dapat lagi mengakses halaman terproteksi tanpa login ulang.
     */
    public function test_logout_sesi_terhapus_tidak_bisa_akses_halaman_terproteksi(): void
    {
        $this->actingAs($this->adminLembaga)
            ->post(route('logout'));

        $this->assertGuest();

        $this->get(route('certificate.index'))
            ->assertRedirect(route('login'));
    }
}
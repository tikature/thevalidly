<?php

namespace Tests\Feature\Iterasi1;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-002 — Pendaftaran Lembaga
 * Iterasi 1 | US02
 *
 * Jumlah test method: 6 (sesuai jumlah AC)
 */
class PendaftaranLembagaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'       => 'super_admin',
            'is_active'  => true,
            'is_primary' => true,
        ]);
    }

    /**
     * AC1: Super Admin dapat mendaftarkan lembaga baru beserta akun
     * admin pertamanya melalui satu form yang sama.
     */
    public function test_super_admin_dapat_mendaftarkan_lembaga_beserta_admin_dalam_satu_form(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'PT Expertindo Training',
                'institution_email'   => 'info@expertindo.com',
                'institution_phone'   => '08123456789',
                'institution_address' => 'Jl. Merdeka No. 1 Purwokerto',
                'admin_name'          => 'Ashley Hardy',
                'admin_email'         => 'ashley@expertindo.com',
                'admin_password'      => 'password123',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('institutions', ['email' => 'info@expertindo.com']);
        $this->assertDatabaseHas('users', ['email' => 'ashley@expertindo.com']);
    }

    /**
     * AC2: Setelah pendaftaran berhasil, lembaga langsung aktif dan
     * admin pertama dapat langsung login menggunakan kredensial yang didaftarkan.
     */
    public function test_setelah_pendaftaran_lembaga_aktif_dan_admin_dapat_login(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'PT Tigafa',
                'institution_email'   => 'info@tigafa.id',
                'institution_phone'   => '08129999999',
                'institution_address' => 'Jl. Sudirman No. 5',
                'admin_name'          => 'Dennis Norris',
                'admin_email'         => 'dennis@tigafa.id',
                'admin_password'      => 'password123',
            ]);

        $institution = Institution::where('email', 'info@tigafa.id')->first();
        $this->assertTrue($institution->is_active);

        $this->post(route('login.post'), [
            'email'    => 'dennis@tigafa.id',
            'password' => 'password123',
        ])->assertRedirect(route('certificate.index'));
    }

    /**
     * AC3: Setiap lembaga mendapatkan identitas unik yang dibuat otomatis
     * oleh sistem dari nama lembaga.
     */
    public function test_sistem_membuat_slug_unik_otomatis_dari_nama_lembaga(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'PT Duta Training',
                'institution_email'   => 'info@duta.com',
                'institution_phone'   => '08111111111',
                'institution_address' => 'Jl. Ahmad Yani No. 10',
                'admin_name'          => 'Budi Santoso',
                'admin_email'         => 'budi@duta.com',
                'admin_password'      => 'password123',
            ]);

        $institution = Institution::where('email', 'info@duta.com')->first();
        $this->assertNotNull($institution->slug);
        $this->assertStringContainsString('pt-duta-training', $institution->slug);
    }

    /**
     * AC4: Ketika email lembaga yang dimasukkan sudah digunakan lembaga lain,
     * sistem menolak pendaftaran dengan pesan yang informatif.
     */
    public function test_pendaftaran_ditolak_jika_email_lembaga_sudah_digunakan(): void
    {
        Institution::factory()->create(['email' => 'duplikat@lembaga.com']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Baru',
                'institution_email'   => 'duplikat@lembaga.com',
                'institution_phone'   => '08100000000',
                'institution_address' => 'Jl. Baru No. 1',
                'admin_name'          => 'Admin Baru',
                'admin_email'         => 'adminbaru@lembaga.com',
                'admin_password'      => 'password123',
            ]);

        $response->assertSessionHasErrors(['institution_email'], null, 'addInstitution');
        $this->assertDatabaseCount('institutions', 1);
    }

    /**
     * AC5: Ketika email admin yang dimasukkan sudah digunakan akun lain
     * di sistem, sistem menolak pendaftaran dengan pesan yang informatif.
     */
    public function test_pendaftaran_ditolak_jika_email_admin_sudah_digunakan(): void
    {
        User::factory()->create(['email' => 'duplikat@admin.com']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => 'Lembaga Sah',
                'institution_email'   => 'unik@lembaga.com',
                'institution_phone'   => '08100000001',
                'institution_address' => 'Jl. Sah No. 1',
                'admin_name'          => 'Admin Duplikat',
                'admin_email'         => 'duplikat@admin.com',
                'admin_password'      => 'password123',
            ]);

        $response->assertSessionHasErrors(['admin_email'], null, 'addInstitution');
        $this->assertDatabaseMissing('institutions', ['email' => 'unik@lembaga.com']);
    }

    /**
     * AC6: Ketika field wajib tidak diisi, sistem menolak penyimpanan
     * dan menunjukkan field mana yang perlu dilengkapi.
     */
    public function test_pendaftaran_ditolak_jika_field_wajib_kosong(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.institutions.store'), [
                'institution_name'    => '',
                'institution_email'   => '',
                'institution_phone'   => '',
                'institution_address' => '',
                'admin_name'          => '',
                'admin_email'         => '',
                'admin_password'      => '',
            ]);

        $response->assertSessionHasErrors(
            ['institution_name', 'institution_email', 'admin_name', 'admin_email', 'admin_password'],
            null,
            'addInstitution'
        );
    }
}
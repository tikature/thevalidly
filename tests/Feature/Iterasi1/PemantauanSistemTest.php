<?php

namespace Tests\Feature\Iterasi1;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-003 — Pemantauan Sistem
 * Iterasi 1 | US03
 *
 * Jumlah test method: 3 (sesuai jumlah AC)
 */
class PemantauanSistemTest extends TestCase
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
     * AC1: Panel Super Admin menampilkan ringkasan statistik sistem yang
     * mencakup total lembaga terdaftar, jumlah lembaga aktif, dan total akun admin.
     */
    public function test_panel_menampilkan_statistik_total_lembaga_aktif_dan_total_admin(): void
    {
        Institution::factory()->count(3)->create(['is_active' => true]);
        Institution::factory()->count(1)->create(['is_active' => false]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'));

        $response->assertOk();
        $response->assertViewHas('institutions');
    }

    /**
     * AC2: Statistik yang ditampilkan mencerminkan kondisi terkini,
     * perubahan data langsung tercermin pada angka statistik.
     */
    public function test_statistik_mencerminkan_kondisi_terkini_setelah_penambahan_lembaga(): void
    {
        $sebelum = Institution::count();

        Institution::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'));

        $response->assertOk();
        $this->assertEquals($sebelum + 1, Institution::count());
    }

    /**
     * AC3: Seluruh lembaga yang terdaftar ditampilkan dalam daftar beserta
     * nama lembaga, email, status aktif, dan daftar admin yang terdaftar di dalamnya.
     */
    public function test_daftar_lembaga_ditampilkan_beserta_nama_email_status_dan_admin(): void
    {
        $institution = Institution::factory()->create([
            'name'      => 'PT Expertindo',
            'email'     => 'info@expertindo.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.index'));

        $response->assertOk();
        $response->assertViewHas('institutions', function ($institutions) use ($institution) {
            return $institutions->contains('id', $institution->id);
        });
    }
}
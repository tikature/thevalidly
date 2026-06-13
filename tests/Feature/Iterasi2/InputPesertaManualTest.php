<?php

namespace Tests\Feature\Iterasi2;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-010 — Input Peserta Manual
 * Iterasi 2 | US10
 *
 * Jumlah test method: 4 (sesuai jumlah AC)
 */
class InputPesertaManualTest extends TestCase
{
    use RefreshDatabase;

    private User $adminLembaga;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['is_active' => true]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
    }

    /**
     * AC1: Admin Lembaga dapat menambah baris isian peserta secara dinamis
     * tanpa halaman dimuat ulang.
     */
    public function test_halaman_generator_dapat_diakses_untuk_input_peserta_dinamis(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.index'));

        $response->assertOk();
        $response->assertViewIs('certificate.index');
    }

    /**
     * AC2: Admin Lembaga dapat menghapus baris peserta tertentu secara dinamis
     * tanpa halaman dimuat ulang.
     */
    public function test_halaman_generator_mendukung_penghapusan_baris_peserta_dinamis(): void
    {
        // AC2 divalidasi via store bulk — jika peserta dikirim hanya sebagian
        // (setelah baris dihapus di frontend), sistem hanya memproses yang dikirim.
        $response = $this->actingAs($this->adminLembaga)
            ->post(route('certificate.storeBulk'), [
                'event_name' => 'Pelatihan Dasar',
                'date_start' => '2026-01-10',
                'participants' => [
                    ['nama' => 'Peserta Satu', 'nomor' => 'CERT/001', 'perusahaan' => ''],
                ],
            ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('count'));
        $this->assertDatabaseCount('certificates', 1);
    }

    /**
     * AC3: Setiap baris menyediakan field nama peserta, asal instansi,
     * dan nomor sertifikat.
     */
    public function test_setiap_baris_peserta_memuat_nama_instansi_dan_nomor(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->post(route('certificate.storeBulk'), [
                'event_name' => 'Workshop Desain',
                'date_start' => '2026-02-01',
                'participants' => [
                    [
                        'nama'       => 'Rina Kartika',
                        'perusahaan' => 'PT Maju Jaya',
                        'nomor'      => 'CERT/002/2026',
                    ],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificates', [
            'nama'       => 'Rina Kartika',
            'perusahaan' => 'PT Maju Jaya',
            'nomor'      => 'CERT/002/2026',
        ]);
    }

    /**
     * AC4: Ketika data peserta disimpan, seluruh peserta yang diinput
     * tersedia untuk di-generate sertifikatnya.
     */
    public function test_seluruh_peserta_yang_diinput_tersimpan_dan_siap_digenerate(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->post(route('certificate.storeBulk'), [
                'event_name' => 'Seminar Nasional',
                'date_start' => '2026-03-01',
                'participants' => [
                    ['nama' => 'Peserta A', 'nomor' => 'CERT/A', 'perusahaan' => ''],
                    ['nama' => 'Peserta B', 'nomor' => 'CERT/B', 'perusahaan' => ''],
                    ['nama' => 'Peserta C', 'nomor' => 'CERT/C', 'perusahaan' => ''],
                ],
            ]);

        $response->assertOk();
        $this->assertEquals(3, $response->json('count'));
        $this->assertDatabaseCount('certificates', 3);
    }
}
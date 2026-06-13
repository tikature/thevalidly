<?php

namespace Tests\Feature\Iterasi2;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-011 — Auto-Numbering Sertifikat
 * Iterasi 2 | US11
 *
 * Catatan: Auto-numbering dikerjakan di sisi frontend (JS) sebelum dikirim ke server.
 * Test ini memvalidasi bahwa nomor yang dihasilkan frontend diterima dan tersimpan
 * benar di database sesuai format yang dikonfigurasi.
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class AutoNumberingSertifikatTest extends TestCase
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
     * AC1: Admin Lembaga dapat menyusun format nomor sertifikat dari kombinasi
     * segmen seperti teks bebas, nomor urut, kode bulan, dan tahun.
     */
    public function test_format_nomor_dari_kombinasi_segmen_diterima_dan_tersimpan(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'nomor'      => 'CERT/001/I/2026',
                'event_name' => 'Pelatihan K3',
                'date_start' => '2026-01-10',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificates', ['nomor' => 'CERT/001/I/2026']);
    }

    /**
     * AC2: Separator antar segmen dapat dikonfigurasi sesuai kebutuhan lembaga.
     */
    public function test_separator_antar_segmen_nomor_dapat_dikonfigurasi(): void
    {
        // Separator titik
        $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'       => 'Siti Rahayu',
                'nomor'      => 'EXP.001.01.2026',
                'event_name' => 'Pelatihan ISO',
                'date_start' => '2026-01-15',
            ]);

        $this->assertDatabaseHas('certificates', ['nomor' => 'EXP.001.01.2026']);

        // Separator strip
        $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'       => 'Ahmad Fauzi',
                'nomor'      => 'EXP-002-01-2026',
                'event_name' => 'Pelatihan ISO',
                'date_start' => '2026-01-15',
            ]);

        $this->assertDatabaseHas('certificates', ['nomor' => 'EXP-002-01-2026']);
    }

    /**
     * AC3: Preview nomor sertifikat untuk peserta pertama tampil langsung
     * saat format dikonfigurasi, tanpa perlu menyimpan terlebih dahulu.
     */
    public function test_halaman_generator_dapat_diakses_untuk_konfigurasi_format_nomor(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.index'));

        $response->assertOk();
        $response->assertViewIs('certificate.index');
    }

    /**
     * AC4: Preview berubah secara langsung setiap kali konfigurasi format diperbarui.
     */
    public function test_halaman_generator_merender_view_untuk_preview_format_nomor(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.index'));

        $response->assertOk();
        $response->assertViewHas('institution');
    }

    /**
     * AC5: Nomor sertifikat yang dihasilkan untuk setiap peserta mengikuti
     * format yang telah dikonfigurasi.
     */
    public function test_nomor_sertifikat_tiap_peserta_tersimpan_sesuai_format(): void
    {
        $participants = [
            ['nama' => 'Peserta 1', 'nomor' => 'CERT/001/I/2026', 'perusahaan' => ''],
            ['nama' => 'Peserta 2', 'nomor' => 'CERT/002/I/2026', 'perusahaan' => ''],
            ['nama' => 'Peserta 3', 'nomor' => 'CERT/003/I/2026', 'perusahaan' => ''],
        ];

        $this->actingAs($this->adminLembaga)
            ->post(route('certificate.storeBulk'), [
                'event_name'   => 'Pelatihan Python',
                'date_start'   => '2026-01-20',
                'participants' => $participants,
            ]);

        foreach ($participants as $p) {
            $this->assertDatabaseHas('certificates', ['nomor' => $p['nomor']]);
        }
    }
}
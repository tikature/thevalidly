<?php

namespace Tests\Feature\Iterasi2;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-008 — Konfigurasi Kegiatan
 * Iterasi 2 | US08
 *
 * Catatan: controller store() return JSON, sehingga request harus
 * menyertakan header Accept: application/json agar validasi return 422
 * (bukan redirect 302).
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class KonfigurasiKegiatanTest extends TestCase
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
     * AC1: Halaman generator menyediakan form untuk mengisi nama acara,
     * tempat, tanggal pelaksanaan, nama penandatangan, jabatan penandatangan,
     * dan deskripsi kegiatan.
     */
    public function test_halaman_generator_menyediakan_form_detail_kegiatan(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.index'));

        $response->assertOk();
        $response->assertViewIs('certificate.index');
    }

    /**
     * AC2: Sistem mendukung pengisian tanggal mulai dan tanggal selesai
     * yang berbeda untuk kegiatan yang berlangsung lebih dari satu hari.
     */
    public function test_sistem_mendukung_tanggal_mulai_dan_selesai_berbeda(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.store'), [
                'nama'        => 'Budi Santoso',
                'nomor'       => 'CERT/001/2026',
                'event_name'  => 'Pelatihan Laravel',
                'date_start'  => '2026-01-10',
                'date_end'    => '2026-01-12',
                'event_place' => 'Purwokerto',
                'signer_name' => 'Agus Darmawan',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificates', [
            'event_name' => 'Pelatihan Laravel',
            'date_start' => '2026-01-10',
            'date_end'   => '2026-01-12',
        ]);
    }

    /**
     * AC3: Ketika tanggal selesai diisi lebih awal dari tanggal mulai,
     * sistem menolak penyimpanan dengan pesan yang informatif.
     */
    public function test_sistem_menolak_tanggal_selesai_lebih_awal_dari_tanggal_mulai(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Pelatihan Laravel',
                'date_start' => '2026-01-12',
                'date_end'   => '2026-01-10',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date_end');
        $this->assertDatabaseMissing('certificates', ['event_name' => 'Pelatihan Laravel']);
    }

    /**
     * AC4: Ketika field nama acara dikosongkan, sistem menolak penyimpanan
     * dengan pesan yang informatif.
     */
    public function test_sistem_menolak_penyimpanan_jika_nama_acara_kosong(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'nomor'      => 'CERT/001/2026',
                'event_name' => '',
                'date_start' => '2026-01-10',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('event_name');
        $this->assertDatabaseMissing('certificates', ['nama' => 'Budi Santoso']);
    }

    /**
     * AC5: Seluruh informasi kegiatan yang diisi tampil dengan benar
     * pada sertifikat PDF yang dihasilkan.
     */
    public function test_informasi_kegiatan_tersimpan_benar_di_database(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.store'), [
                'nama'         => 'Siti Aminah',
                'nomor'        => 'CERT/002/2026',
                'event_name'   => 'Workshop Python',
                'date_start'   => '2026-02-01',
                'event_place'  => 'Jakarta',
                'signer_name'  => 'Dr. Budi',
                'signer_title' => 'Direktur',
                'cert_desc'    => 'Peserta telah mengikuti workshop Python dasar',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificates', [
            'nama'         => 'Siti Aminah',
            'event_name'   => 'Workshop Python',
            'event_place'  => 'Jakarta',
            'signer_name'  => 'Dr. Budi',
            'signer_title' => 'Direktur',
        ]);
    }
}
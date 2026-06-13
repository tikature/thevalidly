<?php

namespace Tests\Feature\Iterasi2;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-016 — Riwayat Sertifikat
 * Iterasi 2 | US16
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class RiwayatSertifikatTest extends TestCase
{
    use RefreshDatabase;

    private User $adminLembaga;
    private User $adminLembagaLain;
    private Institution $institution;
    private Institution $institutionLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['is_active' => true]);
        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);

        $this->institutionLain = Institution::factory()->create(['is_active' => true]);
        $this->adminLembagaLain = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institutionLain->id,
            'is_active'      => true,
        ]);
    }

    /**
     * AC1: Halaman riwayat menampilkan daftar sertifikat yang pernah diterbitkan
     * oleh lembaga, beserta nama peserta, nama kegiatan, tanggal kegiatan,
     * dan tanggal penerbitan.
     */
    public function test_halaman_riwayat_menampilkan_daftar_sertifikat_lembaga(): void
    {
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Budi Santoso',
            'event_name'     => 'Pelatihan K3',
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history'));

        $response->assertOk();
        $response->assertViewHas('certificates', function ($certificates) {
            return $certificates->contains('nama', 'Budi Santoso');
        });
    }

    /**
     * AC2: Admin Lembaga hanya dapat melihat riwayat sertifikat milik
     * lembaganya sendiri.
     */
    public function test_admin_hanya_melihat_riwayat_sertifikat_milik_lembaganya(): void
    {
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Peserta Lembaga Sendiri',
        ]);

        Certificate::factory()->create([
            'institution_id' => $this->institutionLain->id,
            'nama'           => 'Peserta Lembaga Lain',
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history'));

        $response->assertOk();
        $response->assertViewHas('certificates', function ($certificates) {
            return $certificates->contains('nama', 'Peserta Lembaga Sendiri')
                && !$certificates->contains('nama', 'Peserta Lembaga Lain');
        });
    }

    /**
     * AC3: Admin Lembaga dapat mencari sertifikat berdasarkan kata kunci;
     * daftar terfilter sesuai kata kunci yang dimasukkan.
     */
    public function test_pencarian_sertifikat_berdasarkan_kata_kunci_memfilter_daftar(): void
    {
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Ahmad Fauzi',
            'event_name'     => 'Pelatihan ISO',
        ]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Siti Rahayu',
            'event_name'     => 'Workshop Python',
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history', ['search' => 'Ahmad']));

        $response->assertOk();
        $response->assertViewHas('certificates', function ($certificates) {
            return $certificates->contains('nama', 'Ahmad Fauzi')
                && !$certificates->contains('nama', 'Siti Rahayu');
        });
    }

    /**
     * AC4: Admin Lembaga dapat membuka halaman peserta dan halaman verifikasi
     * dari masing-masing entri di halaman riwayat.
     */
    public function test_sertifikat_di_riwayat_memiliki_token_untuk_halaman_peserta_dan_verifikasi(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history'));

        $response->assertOk();
        $response->assertViewHas('certificates', function ($certificates) use ($cert) {
            $found = $certificates->firstWhere('id', $cert->id);
            return $found
                && !empty($found->verification_token)
                && route('certificate.participant', $found->verification_token)
                && route('certificate.verify', $found->verification_token);
        });
    }

    /**
     * AC5: Admin Lembaga dapat menghapus sertifikat dari riwayat;
     * sertifikat yang dihapus tidak lagi muncul di daftar.
     */
    public function test_admin_dapat_menghapus_sertifikat_dari_riwayat(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->actingAs($this->adminLembaga)
            ->delete(route('certificate.destroy', $cert));

        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history'));

        $response->assertViewHas('certificates', function ($certificates) use ($cert) {
            return !$certificates->contains('id', $cert->id);
        });
    }
}
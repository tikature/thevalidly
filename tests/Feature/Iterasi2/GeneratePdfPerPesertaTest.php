<?php

namespace Tests\Feature\Iterasi2;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SKPL-VLDLY-012 — Generate PDF Per Peserta
 * Iterasi 2 | US12
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class GeneratePdfPerPesertaTest extends TestCase
{
    use RefreshDatabase;

    private User $adminLembaga;
    private User $adminLembagaLain;
    private Institution $institution;
    private Institution $institutionLain;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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
     * AC1: Admin Lembaga dapat men-generate sertifikat PDF untuk setiap
     * peserta yang telah diinput.
     */
    public function test_admin_dapat_generate_sertifikat_untuk_peserta(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Pelatihan K3',
                'date_start' => '2026-01-10',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
        $this->assertNotNull($response->json('verification_token'));
    }

    /**
     * AC2: Setelah generate berhasil, tombol unduh PDF tersedia untuk
     * peserta yang bersangkutan.
     */
    public function test_setelah_generate_berhasil_url_pdf_tersedia(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'       => 'Siti Aminah',
                'nomor'      => 'CERT/002/2026',
                'event_name' => 'Workshop Laravel',
                'date_start' => '2026-02-01',
            ]);

        $response->assertOk();
        $this->assertNotNull($response->json('pdf_url'));
        $this->assertNotEmpty($response->json('pdf_url'));
    }

    /**
     * AC3: PDF yang diunduh memuat seluruh informasi yang benar: nama peserta,
     * nomor sertifikat, detail kegiatan, dan aset visual lembaga.
     */
    public function test_sertifikat_tersimpan_dengan_informasi_lengkap_di_database(): void
    {
        $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'         => 'Ahmad Fauzi',
                'nomor'        => 'CERT/003/2026',
                'event_name'   => 'Seminar AI',
                'date_start'   => '2026-03-01',
                'event_place'  => 'Purwokerto',
                'signer_name'  => 'Dr. Budi',
                'signer_title' => 'Direktur',
            ]);

        $this->assertDatabaseHas('certificates', [
            'nama'         => 'Ahmad Fauzi',
            'nomor'        => 'CERT/003/2026',
            'event_name'   => 'Seminar AI',
            'event_place'  => 'Purwokerto',
            'signer_name'  => 'Dr. Budi',
            'signer_title' => 'Direktur',
        ]);
    }

    /**
     * AC4: Aset visual yang termuat pada PDF adalah aset yang aktif pada saat
     * generate dilakukan, meskipun aset lembaga diganti setelahnya.
     */
    public function test_snapshot_aset_tersimpan_saat_generate_tidak_berubah_saat_aset_diganti(): void
    {
        $this->institution->update(['logo_path' => 'institutions/1/logo/logo_lama.png']);

        $this->actingAs($this->adminLembaga)
            ->post(route('certificate.store'), [
                'nama'       => 'Rina Kartika',
                'nomor'      => 'CERT/004/2026',
                'event_name' => 'Pelatihan OSHA',
                'date_start' => '2026-04-01',
            ]);

        $cert = Certificate::where('nomor', 'CERT/004/2026')->first();
        $snapLama = $cert->snap_logo_path;

        // Ganti logo lembaga setelah generate
        $this->institution->update(['logo_path' => 'institutions/1/logo/logo_baru.png']);

        // Snapshot pada sertifikat tidak boleh berubah
        $cert->refresh();
        $this->assertEquals($snapLama, $cert->snap_logo_path);
        $this->assertEquals('institutions/1/logo/logo_lama.png', $cert->snap_logo_path);
    }

    /**
     * AC5: Admin Lembaga hanya dapat mengunduh PDF sertifikat milik
     * lembaganya sendiri; sertifikat lembaga lain tidak dapat diakses.
     */
    public function test_admin_tidak_dapat_mengunduh_pdf_sertifikat_lembaga_lain(): void
    {
        // Buat sertifikat milik lembaga lain
        $certLain = Certificate::factory()->create([
            'institution_id' => $this->institutionLain->id,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.pdf', $certLain->verification_token));

        $response->assertForbidden();
    }
}
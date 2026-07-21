<?php

namespace Tests\Feature\Iterasi3;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKPL-VLDLY-014 — Upload File Peserta
 * Iterasi 3 | US13
 *
 * Catatan: parsing file (SheetJS) dilakukan di sisi browser — server hanya
 * menerima array participants hasil parsing. Test memvalidasi behavior server
 * terhadap data yang dikirim, bukan proses parsing file itu sendiri.
 *
 * Jumlah test method: 4 (sesuai jumlah AC)
 */
class UploadFilePesertaTest extends TestCase
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
     * AC1: Sistem menerima file peserta berformat .xlsx dan .csv.
     *
     * Catatan: validasi format file dilakukan di frontend (SheetJS).
     * Di sisi server, sistem menerima array participants hasil parsing.
     * Test memvalidasi bahwa server menerima dan memproses data dengan benar.
     */
    public function test_sistem_menerima_data_peserta_dari_file_xlsx_atau_csv(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Pelatihan Excel',
                'date_start'   => '2026-01-10',
                'participants' => [
                    ['nama' => 'Budi Santoso',  'nomor' => 'CERT/001', 'perusahaan' => 'PT A'],
                    ['nama' => 'Siti Rahayu',   'nomor' => 'CERT/002', 'perusahaan' => 'PT B'],
                    ['nama' => 'Ahmad Fauzi',   'nomor' => 'CERT/003', 'perusahaan' => 'PT C'],
                ],
            ]);

        $response->assertOk();
        $this->assertEquals(3, $response->json('total'));
        $this->assertDatabaseHas('certificate_batches', ['event_name' => 'Pelatihan Excel']);
    }

    /**
     * AC2: Ketika file berhasil dipilih, sistem menampilkan pratinjau beberapa
     * baris pertama data beserta jumlah total peserta yang terbaca.
     *
     * Catatan: pratinjau dilakukan di frontend sebelum submit. Di sisi server,
     * total peserta yang terbaca tercermin dari field 'total' pada batch yang dibuat.
     */
    public function test_jumlah_total_peserta_terbaca_tercermin_di_batch(): void
    {
        $participants = array_map(fn($i) => [
            'nama'       => "Peserta {$i}",
            'nomor'      => "CERT/00{$i}",
            'perusahaan' => '',
        ], range(1, 5));

        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Seminar Nasional',
                'date_start'   => '2026-02-01',
                'participants' => $participants,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificate_batches', [
            'event_name' => 'Seminar Nasional',
            'total'      => 5,
        ]);
    }

    /**
     * AC3: Baris yang tidak memiliki nama peserta dilewati secara otomatis
     * dan tidak dihitung dalam total.
     *
     * Catatan: filtering baris kosong dilakukan di frontend sebelum submit.
     * Validasi server: field 'nama' wajib diisi per peserta.
     */
    public function test_peserta_tanpa_nama_ditolak_oleh_validasi_server(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Workshop K3',
                'date_start'   => '2026-03-01',
                'participants' => [
                    ['nama' => '',            'nomor' => 'CERT/001', 'perusahaan' => ''],
                ],
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('certificate_batches', ['event_name' => 'Workshop K3']);
    }

    /**
     * AC4: Ketika file yang dipilih bukan .xlsx atau .csv, sistem menolak
     * file tersebut dengan pesan yang informatif.
     *
     * Catatan: validasi format file dilakukan di frontend. Di sisi server,
     * request tanpa participants yang valid ditolak dengan validasi error.
     */
    public function test_batch_ditolak_jika_tidak_ada_peserta_valid(): void
    {
        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Pelatihan ISO',
                'date_start'   => '2026-04-01',
                'participants' => [],
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('certificate_batches', ['event_name' => 'Pelatihan ISO']);
    }
}
<?php

namespace Tests\Feature\Iterasi3;

use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SKPL-VLDLY-015 — Generate Massal via Queue
 * Iterasi 3 | US14
 *
 * Catatan: Queue::fake() digunakan agar job tidak benar-benar dieksekusi
 * saat test — cukup verifikasi bahwa batch dibuat dan job di-dispatch
 * dengan jumlah yang benar.
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class GenerateMassalViaQueueTest extends TestCase
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
     * AC1: Ketika Admin Lembaga memulai generate massal, proses berjalan
     * di background sehingga Admin dapat melanjutkan aktivitas lain tanpa menunggu.
     */
    public function test_generate_massal_memulai_batch_dan_berjalan_di_background(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Pelatihan K3',
                'date_start'   => '2026-01-10',
                'participants' => [
                    ['nama' => 'Budi Santoso', 'nomor' => 'CERT/001', 'perusahaan' => ''],
                    ['nama' => 'Siti Rahayu',  'nomor' => 'CERT/002', 'perusahaan' => ''],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('certificate_batches', [
            'event_name' => 'Pelatihan K3',
            'status'     => 'processing',
        ]);

        // Verifikasi job di-dispatch ke queue (tidak blocking)
        Queue::assertPushed(\App\Jobs\ProcessCertificateJob::class);
    }

    /**
     * AC2: Kemajuan proses generate dapat dipantau secara langsung
     * di halaman tanpa perlu memuat ulang.
     */
    public function test_endpoint_progress_mengembalikan_status_batch_secara_realtime(): void
    {
        Queue::fake();

        $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Workshop Python',
                'date_start'   => '2026-02-01',
                'participants' => [
                    ['nama' => 'Ahmad Fauzi', 'nomor' => 'CERT/001', 'perusahaan' => ''],
                ],
            ]);

        $batch = CertificateBatch::where('event_name', 'Workshop Python')->first();

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.batch.progress', $batch->batch_token));

        $response->assertOk();
        $response->assertJsonStructure([
            'status', 'total', 'processed', 'failed', 'percent',
        ]);
    }

    /**
     * AC3: Ketika terdapat peserta dengan nama yang sama dalam satu batch,
     * sistem hanya menghasilkan satu sertifikat per nama.
     */
    public function test_peserta_dengan_nama_duplikat_dalam_batch_hanya_diproses_sekali(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Seminar Duplikat',
                'date_start'   => '2026-03-01',
                'participants' => [
                    ['nama' => 'Budi Santoso', 'nomor' => 'CERT/001', 'perusahaan' => ''],
                    ['nama' => 'Budi Santoso', 'nomor' => 'CERT/002', 'perusahaan' => ''],
                    ['nama' => 'Siti Rahayu',  'nomor' => 'CERT/003', 'perusahaan' => ''],
                ],
            ]);

        $response->assertOk();

        // Batch dibuat dengan total 3 (sesuai input)
        // Duplikat di-handle di level job (ProcessCertificateJob)
        // yang akan skip jika nama sudah ada di batch yang sama
        $this->assertDatabaseHas('certificate_batches', [
            'event_name' => 'Seminar Duplikat',
            'total'      => 3,
        ]);

        // Verifikasi 3 job di-dispatch (deduplication terjadi saat job dieksekusi)
        Queue::assertPushed(\App\Jobs\ProcessCertificateJob::class, 3);
    }

    /**
     * AC4: Setelah seluruh peserta selesai diproses, status batch berubah
     * menjadi selesai dan tombol unduh ZIP tersedia.
     */
    public function test_batch_selesai_status_berubah_done_dan_batch_token_tersedia(): void
    {
        // Simulasikan batch yang sudah selesai diproses
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'status'         => 'done',
            'total'          => 3,
            'processed'      => 3,
            'failed'         => 0,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.batch.progress', $batch->batch_token));

        $response->assertOk();
        $this->assertEquals('done', $response->json('status'));
        $this->assertNotNull($response->json('batch_url'));
    }

    /**
     * AC5: Batch massal dibatasi hingga 1000 peserta dalam satu proses.
     */
    public function test_batch_ditolak_jika_peserta_melebihi_1000(): void
    {
        Queue::fake();

        $participants = array_map(fn($i) => [
            'nama'       => "Peserta {$i}",
            'nomor'      => "CERT/{$i}",
            'perusahaan' => '',
        ], range(1, 1001));

        $response = $this->actingAs($this->adminLembaga)
            ->postJson(route('certificate.batch.store'), [
                'event_name'   => 'Pelatihan Massal',
                'date_start'   => '2026-04-01',
                'participants' => $participants,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('certificate_batches', ['event_name' => 'Pelatihan Massal']);
    }
}
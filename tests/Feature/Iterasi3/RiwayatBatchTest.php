<?php

namespace Tests\Feature\Iterasi3;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SKPL-VLDLY-017 — Riwayat Batch
 * Iterasi 3 | US17
 *
 * Jumlah test method: 5 (sesuai jumlah AC)
 */
class RiwayatBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $adminLembaga;
    private Institution $institution;

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
    }

    /**
     * AC1: Halaman riwayat batch menampilkan seluruh batch yang pernah dibuat
     * oleh lembaga, beserta judul batch, nama kegiatan, jumlah peserta,
     * status, dan tanggal pembuatan.
     */
    public function test_halaman_riwayat_batch_menampilkan_daftar_batch_lembaga(): void
    {
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'event_name'     => 'Pelatihan K3',
            'status'         => 'done',
            'total'          => 10,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history.batch'));

        $response->assertOk();
        $response->assertViewHas('batches', function ($batches) {
            return $batches->contains('event_name', 'Pelatihan K3');
        });
    }

    /**
     * AC2: Admin Lembaga dapat membuka halaman detail batch yang menampilkan
     * statistik proses (total peserta, berhasil, dan gagal) beserta daftar
     * sertifikat di dalamnya.
     */
    public function test_halaman_detail_batch_menampilkan_statistik_dan_daftar_sertifikat(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'status'         => 'done',
            'total'          => 3,
            'processed'      => 3,
            'failed'         => 0,
        ]);

        Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.batch.detail', $batch->id));

        $response->assertOk();
        $response->assertViewHas('batch', function ($b) use ($batch) {
            return $b->id === $batch->id
                && $b->total === 3
                && $b->processed === 3;
        });
        $response->assertViewHas('certificates');
    }

    /**
     * AC3: Di halaman detail batch, Admin Lembaga dapat mencari sertifikat
     * berdasarkan kata kunci; daftar terfilter sesuai kata kunci yang dimasukkan.
     *
     * Prasyarat: method detail() di CertificateBatchController harus sudah
     * ditambahkan ->when(request('search'), fn($q) => $q->search(request('search')))
     */
    public function test_pencarian_di_detail_batch_memfilter_sertifikat_sesuai_kata_kunci(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'status'         => 'done',
            'total'          => 2,
        ]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Budi Santoso',
            'event_name'     => $batch->event_name,
        ]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Siti Rahayu',
            'event_name'     => $batch->event_name,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.batch.detail', $batch->id) . '?search=Budi');

        $response->assertOk();
        $response->assertViewHas('certificates', function ($certs) {
            return $certs->contains('nama', 'Budi Santoso')
                && !$certs->contains('nama', 'Siti Rahayu');
        });
    }

    /**
     * AC4: Admin Lembaga dapat membuka halaman batch publik dari halaman
     * riwayat batch.
     */
    public function test_batch_memiliki_batch_token_untuk_halaman_publik(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'status'         => 'done',
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('certificate.history.batch'));

        $response->assertOk();
        $response->assertViewHas('batches', function ($batches) use ($batch) {
            $found = $batches->firstWhere('id', $batch->id);
            return $found && !empty($found->batch_token)
                && route('certificate.batch.show', $found->batch_token);
        });
    }

    /**
     * AC5: Ketika Admin Lembaga menghapus sebuah batch, seluruh sertifikat
     * yang tergabung dalam batch tersebut ikut terhapus secara otomatis.
     */
    public function test_hapus_batch_menghapus_seluruh_sertifikat_terkait(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->adminLembaga->id,
            'status'         => 'done',
            'total'          => 3,
        ]);

        $certs = Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->actingAs($this->adminLembaga)
            ->delete(route('certificate.batch.destroy', $batch->id));

        $this->assertDatabaseMissing('certificate_batches', ['id' => $batch->id]);

        foreach ($certs as $cert) {
            $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
        }
    }
}
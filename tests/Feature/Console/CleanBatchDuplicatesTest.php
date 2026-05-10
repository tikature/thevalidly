<?php

namespace Tests\Feature\Console;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Artisan Command batch:clean-duplicates
 *
 * Jalankan: php artisan test --filter CleanBatchDuplicatesTest
 */
class CleanBatchDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    #[Test]
    public function clean_duplicates_reports_no_duplicates_when_table_clean(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);
        Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->artisan('batch:clean-duplicates')
            ->expectsOutput('✓ Tidak ada duplikat ditemukan.')
            ->assertExitCode(0);
    }

    #[Test]
    public function clean_duplicates_removes_duplicate_certificates(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 3,
            'processed'      => 3,
        ]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Budi Santoso',
            'perusahaan'     => 'PT Maju',
            'nomor'          => 'CERT/001/2026',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Budi Santoso',
            'perusahaan'     => 'PT Maju',
            'nomor'          => 'CERT/001/2026-dup',
        ]);

        $this->artisan('batch:clean-duplicates')->assertExitCode(0);

        $this->assertEquals(1, Certificate::where('batch_id', $batch->id)->where('nama', 'Budi Santoso')->count());
    }

    #[Test]
    public function clean_duplicates_keeps_the_earliest_certificate(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        $first = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Sari Dewi',
            'perusahaan'     => null,
            'nomor'          => 'CERT/001',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Sari Dewi',
            'perusahaan'     => null,
            'nomor'          => 'CERT/001-dup',
        ]);

        $this->artisan('batch:clean-duplicates')->assertExitCode(0);

        $remaining = Certificate::where('batch_id', $batch->id)->where('nama', 'Sari Dewi')->first();
        $this->assertEquals($first->id, $remaining->id);
    }

    #[Test]
    public function clean_duplicates_updates_batch_counters_after_cleanup(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 5,
            'processed'      => 5,
            'failed'         => 2,
        ]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Agus Salim',
            'perusahaan'     => 'PT X',
            'nomor'          => 'CERT/001',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Agus Salim',
            'perusahaan'     => 'PT X',
            'nomor'          => 'CERT/001-dup',
        ]);

        $this->artisan('batch:clean-duplicates')->assertExitCode(0);

        $fresh = $batch->fresh();
        $this->assertEquals(1, $fresh->total);
        $this->assertEquals(1, $fresh->processed);
        $this->assertEquals(0, $fresh->failed);
    }

    #[Test]
    public function clean_duplicates_with_specific_batch_id_only_cleans_that_batch(): void
    {
        $batch1 = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);
        $batch2 = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch1->id,
            'nama'           => 'Duplikat A',
            'perusahaan'     => null,
            'nomor'          => 'CERT/001',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch1->id,
            'nama'           => 'Duplikat A',
            'perusahaan'     => null,
            'nomor'          => 'CERT/001-dup',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch2->id,
            'nama'           => 'Duplikat B',
            'perusahaan'     => null,
            'nomor'          => 'CERT/002',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch2->id,
            'nama'           => 'Duplikat B',
            'perusahaan'     => null,
            'nomor'          => 'CERT/002-dup',
        ]);

        $this->artisan('batch:clean-duplicates', ['batch_id' => $batch1->id])
            ->assertExitCode(0);

        $this->assertEquals(1, Certificate::where('batch_id', $batch1->id)->count());
        $this->assertEquals(2, Certificate::where('batch_id', $batch2->id)->count());
    }

    #[Test]
    public function clean_duplicates_outputs_summary_when_duplicates_found(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Citra Lestari',
            'perusahaan'     => null,
            'nomor'          => 'CERT/001',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
            'nama'           => 'Citra Lestari',
            'perusahaan'     => null,
            'nomor'          => 'CERT/001-dup',
        ]);

        $this->artisan('batch:clean-duplicates')
            ->expectsOutput('✓ Total dihapus: 1 sertifikat duplikat.')
            ->expectsOutput('✓ Counter batch sudah disesuaikan.')
            ->assertExitCode(0);
    }
}

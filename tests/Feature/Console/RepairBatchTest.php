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
 * Feature Test: Artisan Command batch:repair
 *
 * Jalankan: php artisan test --filter RepairBatchTest
 */
class RepairBatchTest extends TestCase
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
    public function repair_batch_returns_error_when_batch_not_found(): void
    {
        $this->artisan('batch:repair', ['batch_id' => 99999])
            ->expectsOutput('Batch ID 99999 tidak ditemukan.')
            ->assertExitCode(1);
    }

    #[Test]
    public function repair_batch_marks_stuck_batch_as_done(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
            'total'          => 10,
            'processed'      => 3,
            'failed'         => 1,
        ]);

        Certificate::factory()->count(5)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->artisan('batch:repair', ['batch_id' => $batch->id])
            ->assertExitCode(0);

        $fresh = $batch->fresh();
        $this->assertEquals('done', $fresh->status);
        $this->assertNotNull($fresh->finished_at);
    }

    #[Test]
    public function repair_batch_sets_counters_to_actual_certificate_count(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
            'total'          => 100,
            'processed'      => 50,
            'failed'         => 10,
        ]);

        Certificate::factory()->count(7)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->artisan('batch:repair', ['batch_id' => $batch->id])
            ->assertExitCode(0);

        $fresh = $batch->fresh();
        $this->assertEquals(7, $fresh->total);
        $this->assertEquals(7, $fresh->processed);
        $this->assertEquals(0, $fresh->failed);
    }

    #[Test]
    public function repair_batch_outputs_batch_info(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
            'title'          => 'Workshop Test - Batch 1',
            'event_name'     => 'Workshop Test',
        ]);

        $this->artisan('batch:repair', ['batch_id' => $batch->id])
            ->expectsOutput("Batch: {$batch->displayTitle()}")
            ->expectsOutput('Status: processing')
            ->assertExitCode(0);
    }

    #[Test]
    public function repair_batch_outputs_success_message(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
        ]);

        Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->artisan('batch:repair', ['batch_id' => $batch->id])
            ->expectsOutput('✓ Batch diperbaiki: 3 sertifikat, status → done')
            ->assertExitCode(0);
    }

    #[Test]
    public function repair_batch_works_when_no_certificates_exist(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
            'total'          => 5,
        ]);

        $this->artisan('batch:repair', ['batch_id' => $batch->id])
            ->assertExitCode(0);

        $fresh = $batch->fresh();
        $this->assertEquals('done', $fresh->status);
        $this->assertEquals(0, $fresh->total);
        $this->assertEquals(0, $fresh->processed);
    }
}

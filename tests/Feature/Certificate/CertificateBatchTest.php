<?php

namespace Tests\Feature\Certificate;

use App\Jobs\ProcessCertificateJob;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: CertificateBatchController — store, halaman publik & detail
 *
 * Scope file ini:
 * - Store batch (validasi, dispatch job, unique title)
 * - Halaman publik batch
 * - Halaman detail batch (admin)
 *
 * Tidak termasuk (ada di file dedicated):
 * - Progress polling  → CertificateBatchControllerTest
 * - Delete batch      → CertificateBatchControllerTest
 * - Certificates list → CertificateBatchControllerTest
 * - Download ZIP      → BatchZipDownloadTest / CertificateBatchControllerTest
 * - Job processing    → ProcessCertificateJobTest
 * - Model helpers     → CertificateBatchModelTest
 *
 * Jalankan: php artisan test --filter CertificateBatchTest
 */
class CertificateBatchTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User        $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create(['name' => 'Lembaga Test']);
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    // ── Buat batch ────────────────────────────────────────────

    #[Test]
    public function admin_can_create_a_batch(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [
                    ['nama' => 'Budi Santoso', 'nomor' => 'CERT/001/2026'],
                    ['nama' => 'Sari Dewi',    'nomor' => 'CERT/002/2026'],
                ],
                'event_name' => 'Pelatihan Laravel',
                'event_date' => 'Held on 22-04-26 at Purwokerto',
            ])
            ->assertStatus(200)
            ->assertJsonStructure(['batch_id', 'batch_token', 'total']);

        $this->assertDatabaseHas('certificate_batches', [
            'institution_id' => $this->institution->id,
            'total'          => 2,
            'status'         => 'processing',
        ]);
    }

    #[Test]
    public function batch_dispatches_one_job_per_participant(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [
                    ['nama' => 'Budi',  'nomor' => 'CERT/001/2026'],
                    ['nama' => 'Sari',  'nomor' => 'CERT/002/2026'],
                    ['nama' => 'Agus',  'nomor' => 'CERT/003/2026'],
                ],
                'event_name' => 'Training',
                'event_date' => 'Held on 22-04-26 at Jakarta',
            ]);

        Queue::assertPushed(ProcessCertificateJob::class, 3);
    }

    #[Test]
    public function batch_max_500_participants(): void
    {
        Queue::fake();

        $participants = array_fill(0, 501, [
            'nama'  => 'Peserta',
            'nomor' => 'CERT/001/2026',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => $participants,
                'event_name'   => 'Training',
                'event_date'   => 'Held on 22-04-26 at Jakarta',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function batch_generates_unique_title(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Budi', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Pelatihan Laravel',
                'event_date'   => 'Held on 22-04-26 at Jakarta',
            ]);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Sari', 'nomor' => 'CERT/002/2026']],
                'event_name'   => 'Pelatihan Laravel',
                'event_date'   => 'Held on 22-04-26 at Jakarta',
            ]);

        $this->assertDatabaseHas('certificate_batches', ['title' => 'Pelatihan Laravel - Batch 1']);
        $this->assertDatabaseHas('certificate_batches', ['title' => 'Pelatihan Laravel - Batch 2']);
    }

    #[Test]
    public function guest_cannot_create_batch(): void
    {
        $this->postJson(route('certificate.batch.store'), [])
            ->assertUnauthorized();
    }

    // ── Halaman publik batch ──────────────────────────────────

    #[Test]
    public function batch_public_page_is_accessible(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'done',
        ]);

        $this->get(route('certificate.batch.show', $batch->batch_token))
            ->assertStatus(200);
    }

    #[Test]
    public function batch_public_page_shows_event_name(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'event_name'     => 'Workshop Nasional',
        ]);

        $this->get(route('certificate.batch.show', $batch->batch_token))
            ->assertSee('Workshop Nasional');
    }

    // ── Halaman detail batch (admin) ──────────────────────────

    #[Test]
    public function admin_can_access_batch_detail(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('certificate.batch.detail', $batch->id))
            ->assertStatus(200);
    }

    #[Test]
    public function admin_cannot_access_other_institution_batch_detail(): void
    {
        $otherInst  = Institution::factory()->create();
        $otherBatch = CertificateBatch::factory()->create([
            'institution_id' => $otherInst->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('certificate.batch.detail', $otherBatch->id))
            ->assertNotFound();
    }
}

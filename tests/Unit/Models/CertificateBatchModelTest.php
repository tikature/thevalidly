<?php

namespace Tests\Unit\Models;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: CertificateBatch Model
 *
 * Jalankan: php artisan test --filter CertificateBatchModelTest
 */
class CertificateBatchModelTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create(['name' => 'Lembaga Test']);
        $this->user        = User::factory()->adminOf($this->institution)->create();
    }

    // ══════════════════════════════════════════════
    // Auto UUID batch_token
    // ══════════════════════════════════════════════

    #[Test]
    public function batch_auto_generates_batch_token_on_create(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_token'    => null, // paksa trigger boot()
        ]);

        $this->assertNotNull($batch->fresh()->batch_token);
        $this->assertEquals(36, strlen($batch->fresh()->batch_token));
    }

    #[Test]
    public function each_batch_has_unique_token(): void
    {
        $batches = CertificateBatch::factory()->count(5)->create([
            'institution_id' => $this->institution->id,
        ]);

        $tokens = $batches->pluck('batch_token')->unique();
        $this->assertCount(5, $tokens);
    }

    // ══════════════════════════════════════════════
    // generateTitle()
    // ══════════════════════════════════════════════

    #[Test]
    public function generate_title_returns_batch_1_for_first_batch(): void
    {
        $title = CertificateBatch::generateTitle('Pelatihan Laravel', $this->institution->id);
        $this->assertEquals('Pelatihan Laravel - Batch 1', $title);
    }

    #[Test]
    public function generate_title_increments_batch_number_for_same_event(): void
    {
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'event_name'     => 'Seminar AI',
        ]);

        $title = CertificateBatch::generateTitle('Seminar AI', $this->institution->id);
        $this->assertEquals('Seminar AI - Batch 2', $title);
    }

    #[Test]
    public function generate_title_is_independent_per_institution(): void
    {
        $otherInstitution = Institution::factory()->create();

        CertificateBatch::factory()->create([
            'institution_id' => $otherInstitution->id,
            'event_name'     => 'Workshop PHP',
        ]);

        // Di institution kita, event name sama → tetap Batch 1
        $title = CertificateBatch::generateTitle('Workshop PHP', $this->institution->id);
        $this->assertEquals('Workshop PHP - Batch 1', $title);
    }

    #[Test]
    public function generate_title_truncates_long_event_name_to_200_chars(): void
    {
        $longName = str_repeat('A', 250);
        $title    = CertificateBatch::generateTitle($longName, $this->institution->id);
        $expected = str_repeat('A', 200) . ' - Batch 1';
        $this->assertEquals($expected, $title);
    }

    // ══════════════════════════════════════════════
    // displayTitle()
    // ══════════════════════════════════════════════

    #[Test]
    public function display_title_returns_title_when_set(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'title'          => 'Pelatihan K3 - Batch 1',
            'event_name'     => 'Pelatihan K3',
        ]);

        $this->assertEquals('Pelatihan K3 - Batch 1', $batch->displayTitle());
    }

    #[Test]
    public function display_title_falls_back_to_event_name_when_title_is_null(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'title'          => null,
            'event_name'     => 'Pelatihan K3',
        ]);

        $this->assertEquals('Pelatihan K3', $batch->displayTitle());
    }

    // ══════════════════════════════════════════════
    // progressPercent()
    // ══════════════════════════════════════════════

    #[Test]
    public function progress_percent_returns_0_when_total_is_0(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 0,
            'processed'      => 0,
        ]);

        $this->assertEquals(0, $batch->progressPercent());
    }

    #[Test]
    public function progress_percent_returns_50_when_half_processed(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 10,
            'processed'      => 5,
        ]);

        $this->assertEquals(50, $batch->progressPercent());
    }

    #[Test]
    public function progress_percent_returns_100_when_all_processed(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 8,
            'processed'      => 8,
        ]);

        $this->assertEquals(100, $batch->progressPercent());
    }

    #[Test]
    public function progress_percent_rounds_correctly(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 3,
            'processed'      => 1, // 33.33...% → dibulatkan ke 33
        ]);

        $this->assertEquals(33, $batch->progressPercent());
    }

    // ══════════════════════════════════════════════
    // isDone()
    // ══════════════════════════════════════════════

    #[Test]
    public function is_done_returns_true_when_status_is_done(): void
    {
        $batch = CertificateBatch::factory()->done()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->assertTrue($batch->isDone());
    }

    #[Test]
    public function is_done_returns_false_when_status_is_processing(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
        ]);

        $this->assertFalse($batch->isDone());
    }

    // ══════════════════════════════════════════════
    // batchUrl()
    // ══════════════════════════════════════════════

    #[Test]
    public function batch_url_contains_batch_token(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->assertStringContainsString($batch->batch_token, $batch->batchUrl());
    }

    #[Test]
    public function batch_url_contains_batch_path(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->assertStringContainsString('/batch/', $batch->batchUrl());
    }

    // ══════════════════════════════════════════════
    // Relasi
    // ══════════════════════════════════════════════

    #[Test]
    public function batch_belongs_to_institution(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->assertInstanceOf(Institution::class, $batch->institution);
        $this->assertEquals($this->institution->id, $batch->institution->id);
    }

    #[Test]
    public function batch_belongs_to_issued_by_user(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $batch->issuedBy);
        $this->assertEquals($this->user->id, $batch->issuedBy->id);
    }

    #[Test]
    public function batch_has_many_certificates(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->assertCount(3, $batch->certificates);
        $batch->certificates->each(
            fn($c) => $this->assertEquals($batch->id, $c->batch_id)
        );
    }

    // ══════════════════════════════════════════════
    // Casts
    // ══════════════════════════════════════════════

    #[Test]
    public function failed_entries_is_cast_to_array(): void
    {
        $entries = [['nama' => 'Budi', 'reason' => 'Duplikat']];

        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'failed_entries' => $entries,
        ]);

        $this->assertIsArray($batch->fresh()->failed_entries);
        $this->assertEquals($entries, $batch->fresh()->failed_entries);
    }

    #[Test]
    public function started_at_is_cast_to_datetime(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $batch->started_at);
    }

    #[Test]
    public function finished_at_is_null_when_not_done(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
            'finished_at'    => null,
        ]);

        $this->assertNull($batch->finished_at);
    }
}

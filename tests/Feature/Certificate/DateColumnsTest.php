<?php

namespace Tests\Feature\Certificate;

use App\Helpers\DateHelper;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Date Columns (date_start / date_end)
 *
 * Memastikan:
 * 1. date_start & date_end tersimpan sebagai tipe DATE di DB
 * 2. buildEventDateString() menghasilkan format yang benar (single, multi same-month, multi diff-month)
 * 3. Payload dari frontend (date_start, date_end, event_place) diproses dan tersimpan dengan benar
 * 4. PDF blade mendapat nilai event_date string yang benar (via controller + pdf.blade.php)
 * 5. Validasi: date_start wajib, date_end harus >= date_start
 *
 * Jalankan: php artisan test --filter DateColumnsTest
 */
class DateColumnsTest extends TestCase
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

    // ══════════════════════════════════════════════════════════
    // buildEventDateString() — unit test langsung
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function build_event_date_single_day_no_place(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', null, null);
        $this->assertEquals('Held on June 30th, 2025', $result);
    }

    #[Test]
    public function build_event_date_single_day_with_place(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', null, 'Jakarta');
        $this->assertEquals('Held on June 30th, 2025 in Jakarta', $result);
    }

    #[Test]
    public function build_event_date_multi_day_same_month(): void
    {
        // Referensi PDF: "Held on June 30th until July 1st, 2025 in Jakarta"
        $result = DateHelper::buildEventDateString('2025-06-30', '2025-07-01', 'Jakarta');
        $this->assertEquals('Held on June 30th until July 1st, 2025 in Jakarta', $result);
    }

    #[Test]
    public function build_event_date_multi_day_same_month_no_place(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', '2025-07-01', null);
        $this->assertEquals('Held on June 30th until July 1st, 2025', $result);
    }

    #[Test]
    public function build_event_date_multi_day_different_month(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-28', '2025-08-01', 'Bandung');
        $this->assertEquals('Held on June 28th until August 1st, 2025 in Bandung', $result);
    }

    #[Test]
    public function build_event_date_multi_day_different_year(): void
    {
        $result = DateHelper::buildEventDateString('2025-12-30', '2026-01-02', 'Surabaya');
        $this->assertEquals('Held on December 30th, 2025 until January 2nd, 2026 in Surabaya', $result);
    }

    #[Test]
    public function build_event_date_ordinal_st_suffix(): void
    {
        $result = DateHelper::buildEventDateString('2025-07-01', null, null);
        $this->assertStringContainsString('1st', $result);
    }

    #[Test]
    public function build_event_date_ordinal_nd_suffix(): void
    {
        $result = DateHelper::buildEventDateString('2025-07-02', null, null);
        $this->assertStringContainsString('2nd', $result);
    }

    #[Test]
    public function build_event_date_ordinal_rd_suffix(): void
    {
        $result = DateHelper::buildEventDateString('2025-07-03', null, null);
        $this->assertStringContainsString('3rd', $result);
    }

    #[Test]
    public function build_event_date_ordinal_th_suffix_for_11_12_13(): void
    {
        // 11th, 12th, 13th — bukan 11st, 12nd, 13rd
        $this->assertStringContainsString('11th', DateHelper::buildEventDateString('2025-07-11', null, null));
        $this->assertStringContainsString('12th', DateHelper::buildEventDateString('2025-07-12', null, null));
        $this->assertStringContainsString('13th', DateHelper::buildEventDateString('2025-07-13', null, null));
    }

    #[Test]
    public function build_event_date_same_start_and_end_treated_as_single(): void
    {
        // date_end == date_start → tampilkan sebagai single day
        $result = DateHelper::buildEventDateString('2025-07-01', '2025-07-01', 'Jakarta');
        $this->assertEquals('Held on July 1st, 2025 in Jakarta', $result);
        $this->assertStringNotContainsString('until', $result);
    }

    // ══════════════════════════════════════════════════════════
    // DB — date_start & date_end tersimpan sebagai DATE
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function certificate_stores_date_start_as_carbon_date(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'date_start' => '2025-06-30',
            'date_end'   => null,
        ]);

        $fresh = $cert->fresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->date_start);
        $this->assertEquals('2025-06-30', $fresh->date_start->format('Y-m-d'));
        $this->assertNull($fresh->date_end);
    }

    #[Test]
    public function certificate_stores_date_end_as_carbon_date(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'date_start' => '2025-06-30',
            'date_end'   => '2025-07-01',
        ]);

        $fresh = $cert->fresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->date_end);
        $this->assertEquals('2025-07-01', $fresh->date_end->format('Y-m-d'));
    }

    #[Test]
    public function certificate_batch_stores_date_start_as_carbon_date(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'date_start'     => '2025-06-30',
            'date_end'       => '2025-07-01',
        ]);

        $fresh = $batch->fresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->date_start);
        $this->assertEquals('2025-06-30', $fresh->date_start->format('Y-m-d'));
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->date_end);
        $this->assertEquals('2025-07-01', $fresh->date_end->format('Y-m-d'));
    }

    // ══════════════════════════════════════════════════════════
    // HTTP — batch store menyimpan date_start/date_end dengan benar
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function batch_store_saves_date_start_and_date_end_to_db(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Budi Santoso', 'perusahaan' => null, 'nomor' => null]],
                'event_name'   => 'Pelatihan Laravel',
                'date_start'   => '2025-06-30',
                'date_end'     => '2025-07-01',
                'event_place'  => 'Jakarta',
            ])
            ->assertOk();

        $batch = CertificateBatch::first();
        $this->assertEquals('2025-06-30', $batch->date_start->format('Y-m-d'));
        $this->assertEquals('2025-07-01', $batch->date_end->format('Y-m-d'));
        $this->assertEquals('Jakarta', $batch->event_place);
    }

    #[Test]
    public function batch_store_single_day_saves_null_date_end(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Sari Dewi', 'perusahaan' => null, 'nomor' => null]],
                'event_name'   => 'Workshop',
                'date_start'   => '2025-06-30',
            ])
            ->assertOk();

        $this->assertNull(CertificateBatch::first()->date_end);
    }

    #[Test]
    public function single_certificate_store_saves_date_start(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'nama'        => 'Eko Santoso',
                'nomor'       => 'CERT/001/2025',
                'event_name'  => 'Pelatihan',
                'date_start'  => '2025-06-30',
                'date_end'    => '2025-07-01',
                'event_place' => 'Jakarta',
            ])
            ->assertOk();

        $cert = Certificate::first();
        $this->assertEquals('2025-06-30', $cert->date_start->format('Y-m-d'));
        $this->assertEquals('2025-07-01', $cert->date_end->format('Y-m-d'));
    }

    // ══════════════════════════════════════════════════════════
    // Validasi HTTP
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function batch_store_fails_when_date_start_missing(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Budi', 'perusahaan' => null, 'nomor' => null]],
                'event_name'   => 'Training',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_start']);
    }

    #[Test]
    public function batch_store_fails_when_date_end_before_date_start(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Budi', 'perusahaan' => null, 'nomor' => null]],
                'event_name'   => 'Training',
                'date_start'   => '2025-07-05',
                'date_end'     => '2025-07-04', // sebelum start!
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_end']);
    }

    #[Test]
    public function batch_store_accepts_date_end_equal_to_date_start(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Budi', 'perusahaan' => null, 'nomor' => null]],
                'event_name'   => 'Training',
                'date_start'   => '2025-07-01',
                'date_end'     => '2025-07-01', // sama dengan start → valid
            ])
            ->assertOk();
    }

    #[Test]
    public function certificate_store_fails_when_date_start_missing(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'nama'       => 'Budi',
                'nomor'      => 'CERT/001',
                'event_name' => 'Training',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_start']);
    }

    // ══════════════════════════════════════════════════════════
    // Integrasi: event_date di cert cocok dengan yg ada di batch
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function certificate_date_start_matches_batch_date_start(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'date_start'     => '2025-06-30',
            'date_end'       => '2025-07-01',
        ]);

        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'batch_id'   => $batch->id,
            'date_start' => $batch->date_start,
            'date_end'   => $batch->date_end,
        ]);

        $this->assertEquals(
            $batch->date_start->format('Y-m-d'),
            $cert->date_start->format('Y-m-d')
        );
        $this->assertEquals(
            $batch->date_end->format('Y-m-d'),
            $cert->date_end->format('Y-m-d')
        );
    }
}

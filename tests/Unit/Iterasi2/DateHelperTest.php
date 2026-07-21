<?php

namespace Tests\Unit\Iterasi2;

use App\Helpers\DateHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: DateHelper — Iterasi 2
 *
 * Lingkup: ordinal() dan buildEventDateString(), dipakai untuk memformat
 * tanggal kegiatan pada sertifikat yang di-generate (US-09 Konfigurasi
 * Kegiatan & US-12 Generate PDF per Peserta).
 *
 * Jalankan: php artisan test --filter DateHelperTest
 */
class DateHelperTest extends TestCase
{
    // ══════════════════════════════════════════════
    // ordinal()
    // ══════════════════════════════════════════════

    #[Test]
    public function ordinal_handles_first_second_third(): void
    {
        $this->assertEquals('1st', DateHelper::ordinal(1));
        $this->assertEquals('2nd', DateHelper::ordinal(2));
        $this->assertEquals('3rd', DateHelper::ordinal(3));
    }

    #[Test]
    public function ordinal_handles_regular_th(): void
    {
        $this->assertEquals('4th', DateHelper::ordinal(4));
        $this->assertEquals('10th', DateHelper::ordinal(10));
        $this->assertEquals('30th', DateHelper::ordinal(30));
    }

    #[Test]
    public function ordinal_handles_the_11th_12th_13th_exception(): void
    {
        // Pengecualian: 11, 12, 13 selalu "th" walau berakhiran 1/2/3
        $this->assertEquals('11th', DateHelper::ordinal(11));
        $this->assertEquals('12th', DateHelper::ordinal(12));
        $this->assertEquals('13th', DateHelper::ordinal(13));
    }

    #[Test]
    public function ordinal_handles_21st_22nd_23rd(): void
    {
        $this->assertEquals('21st', DateHelper::ordinal(21));
        $this->assertEquals('22nd', DateHelper::ordinal(22));
        $this->assertEquals('23rd', DateHelper::ordinal(23));
    }

    #[Test]
    public function ordinal_handles_111th_112th_113th_exception_past_100(): void
    {
        $this->assertEquals('111th', DateHelper::ordinal(111));
        $this->assertEquals('112th', DateHelper::ordinal(112));
        $this->assertEquals('121st', DateHelper::ordinal(121));
    }

    // ══════════════════════════════════════════════
    // buildEventDateString() — single day
    // ══════════════════════════════════════════════

    #[Test]
    public function single_day_event_with_place(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', null, 'Jakarta');
        $this->assertEquals('Held on June 30th, 2025 in Jakarta', $result);
    }

    #[Test]
    public function single_day_event_without_place(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', null, null);
        $this->assertEquals('Held on June 30th, 2025', $result);
    }

    #[Test]
    public function date_end_equal_to_date_start_is_treated_as_single_day(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', '2025-06-30', 'Bandung');
        $this->assertEquals('Held on June 30th, 2025 in Bandung', $result);
    }

    // ══════════════════════════════════════════════
    // buildEventDateString() — multi day, same year
    // ══════════════════════════════════════════════

    #[Test]
    public function multi_day_event_same_month_same_year(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', '2025-07-01', 'Jakarta');
        $this->assertEquals('Held on June 30th until July 1st, 2025 in Jakarta', $result);
    }

    #[Test]
    public function multi_day_event_different_month_same_year_writes_year_once(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', '2025-08-01', 'Bandung');
        $this->assertEquals('Held on June 30th until August 1st, 2025 in Bandung', $result);
    }

    // ══════════════════════════════════════════════
    // buildEventDateString() — multi day, different year
    // ══════════════════════════════════════════════

    #[Test]
    public function multi_day_event_different_year_writes_year_on_both_sides(): void
    {
        $result = DateHelper::buildEventDateString('2025-06-30', '2026-01-05', null);
        $this->assertEquals('Held on June 30th, 2025 until January 5th, 2026', $result);
    }

    #[Test]
    public function multi_day_event_different_year_with_place(): void
    {
        $result = DateHelper::buildEventDateString('2025-12-30', '2026-01-02', 'Purwokerto');
        $this->assertEquals('Held on December 30th, 2025 until January 2nd, 2026 in Purwokerto', $result);
    }
}

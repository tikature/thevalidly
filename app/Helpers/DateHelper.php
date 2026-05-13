<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Helper untuk memformat tanggal kegiatan menjadi string seperti di sertifikat.
 *
 * Contoh output:
 *   Single day  : "Held on June 30th, 2025 in Jakarta"
 *   Multi day   : "Held on June 30th until July 1st, 2025 in Jakarta"
 *   Beda bulan  : "Held on June 30th, 2025 until August 1st, 2025 in Bandung"
 */
class DateHelper
{
    private static array $months = [
        1  => 'January', 2  => 'February', 3  => 'March',    4  => 'April',
        5  => 'May',      6  => 'June',     7  => 'July',     8  => 'August',
        9  => 'September',10 => 'October',  11 => 'November', 12 => 'December',
    ];

    public static function ordinal(int $n): string
    {
        $suffix = ['th', 'st', 'nd', 'rd'];
        $v      = $n % 100;
        return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
    }

    /**
     * Bangun string "Held on ..." dari komponen tanggal terpisah.
     *
     * @param  string       $dateStart  Format: Y-m-d
     * @param  string|null  $dateEnd    Format: Y-m-d atau null
     * @param  string|null  $place      Nama kota/tempat
     */
    public static function buildEventDateString(string $dateStart, ?string $dateEnd, ?string $place): string
    {
        $start = Carbon::parse($dateStart);

        if ($dateEnd && $dateEnd !== $dateStart) {
            $end = Carbon::parse($dateEnd);

            if ($start->year === $end->year) {
                // Tahun sama: tulis tahun sekali di akhir
                // "June 30th until July 1st, 2025" (sesuai referensi PDF)
                $dateStr = self::$months[$start->month] . ' ' . self::ordinal($start->day)
                    . ' until ' . self::$months[$end->month] . ' ' . self::ordinal($end->day)
                    . ', ' . $end->year;
            } else {
                // Beda tahun: tahun ditulis di tiap sisi
                // "June 30th, 2025 until January 5th, 2026"
                $dateStr = self::$months[$start->month] . ' ' . self::ordinal($start->day) . ', ' . $start->year
                    . ' until ' . self::$months[$end->month] . ' ' . self::ordinal($end->day) . ', ' . $end->year;
            }
        } else {
            $dateStr = self::$months[$start->month] . ' ' . self::ordinal($start->day) . ', ' . $start->year;
        }

        return 'Held on ' . $dateStr . ($place ? ' in ' . $place : '');
    }
}

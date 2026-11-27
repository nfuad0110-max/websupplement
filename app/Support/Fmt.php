<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/** Pemformat angka dan tanggal berbahasa Indonesia. */
class Fmt
{
    const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    const MONTHS_LONG  = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const DAYS         = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private static function c(string|CarbonInterface $d): CarbonInterface
    {
        return $d instanceof CarbonInterface ? $d : Carbon::parse($d);
    }

    /** Rp 85.000 */
    public static function rp(int|float $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.');
    }

    /** 20 Des 2026 */
    public static function date(string|CarbonInterface $d): string
    {
        $d = self::c($d);
        return $d->day . ' ' . self::MONTHS_SHORT[$d->month - 1] . ' ' . $d->year;
    }

    /** Okt 2027 atau Oktober 2027 */
    public static function monthYear(string|CarbonInterface $d, bool $long = false): string
    {
        $d = self::c($d);
        return ($long ? self::MONTHS_LONG : self::MONTHS_SHORT)[$d->month - 1] . ' ' . $d->year;
    }

    /** 24 November 2026 */
    public static function dateLong(string|CarbonInterface $d): string
    {
        $d = self::c($d);
        return $d->day . ' ' . self::MONTHS_LONG[$d->month - 1] . ' ' . $d->year;
    }

    /** SELASA, 24 NOVEMBER 2026 */
    public static function dayDateLong(string|CarbonInterface $d): string
    {
        $d = self::c($d);
        return mb_strtoupper(self::DAYS[$d->dayOfWeek] . ', ' . self::dateLong($d));
    }

    /** 0812-3456-7801 -> 6281234567801 */
    public static function waNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        return str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
    }
}

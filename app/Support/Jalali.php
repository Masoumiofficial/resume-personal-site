<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Gregorian ⇄ Jalali (Solar Hijri) conversion and Persian formatting.
 *
 * Pure PHP, no ext-intl requirement (the host's extension list is unconfirmed).
 * Algorithm: Borkowski's leap-year "breaks" as used by the widely deployed
 * jalaali-js library; valid for Jalali years -61 … 3177.
 */
final class Jalali
{
    public const MONTHS = [
        1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    private const BREAKS = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
        1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    ];

    /** @return array{0:int,1:int,2:int} [jy, jm, jd] */
    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
        return self::d2j(self::g2d($gy, $gm, $gd));
    }

    /** @return array{0:int,1:int,2:int} [gy, gm, gd] */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > self::monthLength($jy, $jm)) {
            throw new InvalidArgumentException("Invalid Jalali date {$jy}/{$jm}/{$jd}");
        }

        return self::d2g(self::j2d($jy, $jm, $jd));
    }

    public static function isLeapYear(int $jy): bool
    {
        return self::jalCal($jy)['leap'] === 0;
    }

    public static function monthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }

        return self::isLeapYear($jy) ? 30 : 29;
    }

    /**
     * Format a moment for display: converted to the display timezone, then to
     * Jalali. Supported tokens: Y (year) m/n (month 2-digit / plain)
     * d/j (day 2-digit / plain) F (month name) H i (24h time). "\" escapes.
     */
    public static function format(?DateTimeInterface $moment, string $pattern = 'j F Y', bool $persianDigits = true): string
    {
        if ($moment === null) {
            return '';
        }

        $local = DateTimeImmutable::createFromInterface($moment)
            ->setTimezone(new DateTimeZone((string) config('app.display_timezone', 'Asia/Tehran')));

        [$jy, $jm, $jd] = self::fromGregorian((int) $local->format('Y'), (int) $local->format('n'), (int) $local->format('j'));

        $out = '';
        $len = strlen($pattern);
        for ($i = 0; $i < $len; $i++) {
            $c = $pattern[$i];
            if ($c === '\\' && $i + 1 < $len) {
                $out .= $pattern[++$i];

                continue;
            }
            $out .= match ($c) {
                'Y' => (string) $jy,
                'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
                'n' => (string) $jm,
                'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
                'j' => (string) $jd,
                'F' => self::MONTHS[$jm],
                'H' => $local->format('H'),
                'i' => $local->format('i'),
                default => $c,
            };
        }

        return $persianDigits ? self::digits($out) : $out;
    }

    /** Convert ASCII (and Arabic-Indic) digits to Persian digits. */
    public static function digits(string|int $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
            '٠' => '۰', '١' => '۱', '٢' => '۲', '٣' => '۳', '٤' => '۴',
            '٥' => '۵', '٦' => '۶', '٧' => '۷', '٨' => '۸', '٩' => '۹',
        ]);
    }

    /** Convert Persian/Arabic-Indic digits back to ASCII (for parsing form input). */
    public static function asciiDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /** Group thousands with the Persian separator: 7500000 → ۷٬۵۰۰٬۰۰۰ */
    public static function number(int $value): string
    {
        return self::digits(number_format($value, 0, '.', '٬'));
    }

    // ------------------------------------------------------------------
    // Algorithm (port of jalaali-js, MIT). intdiv() and % truncate toward
    // zero exactly like the JavaScript ~~(a / b) and a % b it is based on.
    // ------------------------------------------------------------------

    /** @return array{leap:int, gy:int, march:int} */
    private static function jalCal(int $jy): array
    {
        $bl = count(self::BREAKS);
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = self::BREAKS[0];

        if ($jy < $jp || $jy >= self::BREAKS[$bl - 1]) {
            throw new InvalidArgumentException("Jalali year {$jy} is out of the supported range");
        }

        $jump = 0;
        for ($i = 1; $i < $bl; $i++) {
            $jm = self::BREAKS[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $leapJ += intdiv($jump, 33) * 8 + intdiv($jump % 33, 4);
            $jp = $jm;
        }

        $n = $jy - $jp;
        $leapJ += intdiv($n, 33) * 8 + intdiv(($n % 33) + 3, 4);
        if (($jump % 33) === 4 && $jump - $n === 4) {
            $leapJ++;
        }

        $leapG = intdiv($gy, 4) - intdiv((intdiv($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;

        if ($jump - $n < 6) {
            $n = $n - $jump + intdiv($jump + 4, 33) * 33;
        }
        $leap = ((($n + 1) % 33) - 1) % 4;
        if ($leap === -1) {
            $leap = 4;
        }

        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = intdiv(($gy + intdiv($gm - 8, 6) + 100100) * 1461, 4)
            + intdiv(153 * (($gm + 9) % 12) + 2, 5)
            + $gd - 34840408;

        return $d - intdiv(intdiv($gy + 100100 + intdiv($gm - 8, 6), 100) * 3, 4) + 752;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + intdiv(intdiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = intdiv($j % 1461, 4) * 5 + 308;
        $gd = intdiv($i % 153, 5) + 1;
        $gm = (intdiv($i, 153) % 12) + 1;
        $gy = intdiv($j, 1461) - 100100 + intdiv(8 - $gm, 6);

        return [$gy, $gm, $gd];
    }

    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $r = self::jalCal($jy);

        return self::g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31 - intdiv($jm, 7) * ($jm - 7) + $jd - 1;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function d2j(int $jdn): array
    {
        [$gy] = self::d2g($jdn);
        $jy = $gy - 621;
        $r = self::jalCal($jy);
        $k = $jdn - self::g2d($gy, 3, $r['march']);

        if ($k >= 0) {
            if ($k <= 185) {
                return [$jy, 1 + intdiv($k, 31), ($k % 31) + 1];
            }
            $k -= 186;
        } else {
            $jy--;
            $k += 179;
            if ($r['leap'] === 1) {
                $k++;
            }
        }

        return [$jy, 7 + intdiv($k, 30), ($k % 30) + 1];
    }
}

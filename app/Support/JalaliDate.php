<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Jalali form input <-> Gregorian storage.
 *
 * Resume dates are often only a year («۱۳۹۲») or a month («۱۴۰۲/۰۱»), so dates
 * are stored as the first Gregorian day of that period plus a precision flag
 * (year | month | day). Datetimes (published_at) are entered in the display
 * timezone (Asia/Tehran) and stored in UTC.
 */
final class JalaliDate
{
    public const PRECISIONS = ['year', 'month', 'day'];

    /**
     * Parse «1392», «1402/01», «1402/1/15» (Persian or Latin digits; "/" or "-").
     *
     * @return array{0: CarbonImmutable, 1: string}|null [date, precision]; null for empty input
     *
     * @throws InvalidArgumentException for malformed or impossible dates
     */
    public static function parse(?string $input): ?array
    {
        $value = trim(Jalali::asciiDigits((string) $input));
        if ($value === '') {
            return null;
        }

        if (! preg_match('#^(\d{4})(?:[/-](\d{1,2}))?(?:[/-](\d{1,2}))?$#', $value, $m)) {
            throw new InvalidArgumentException('format');
        }

        $jy = (int) $m[1];
        $jm = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : null;
        $jd = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null;

        if ($jy < 1300 || $jy > 1500 || ($jm !== null && ($jm < 1 || $jm > 12))) {
            throw new InvalidArgumentException('range');
        }
        if ($jd !== null && ($jd < 1 || $jd > Jalali::monthLength($jy, (int) $jm))) {
            throw new InvalidArgumentException('range');
        }

        $precision = $jd !== null ? 'day' : ($jm !== null ? 'month' : 'year');
        [$gy, $gm, $gd] = Jalali::toGregorian($jy, $jm ?? 1, $jd ?? 1);

        return [CarbonImmutable::create($gy, $gm, $gd, 0, 0, 0, 'UTC'), $precision];
    }

    /** Value for a form input, e.g. «1402/01» (Latin digits so editing is predictable). */
    public static function toInput(?DateTimeInterface $date, ?string $precision = 'day'): string
    {
        if ($date === null) {
            return '';
        }
        [$jy, $jm, $jd] = Jalali::fromGregorian((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        return match ($precision) {
            'year' => (string) $jy,
            'month' => sprintf('%d/%02d', $jy, $jm),
            default => sprintf('%d/%02d/%02d', $jy, $jm, $jd),
        };
    }

    /** Human display: «۱۳۹۲»، «فروردین ۱۴۰۲»، «۱۵ فروردین ۱۴۰۲». */
    public static function display(?DateTimeInterface $date, ?string $precision = 'day'): string
    {
        if ($date === null) {
            return '';
        }
        [$jy, $jm, $jd] = Jalali::fromGregorian((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        return Jalali::digits(match ($precision) {
            'year' => (string) $jy,
            'month' => Jalali::MONTHS[$jm].' '.$jy,
            default => $jd.' '.Jalali::MONTHS[$jm].' '.$jy,
        });
    }

    /** «1402/01 تا اکنون» style range for resume entries. */
    public static function range(?DateTimeInterface $start, ?DateTimeInterface $end, ?string $precision, string $present = 'اکنون'): string
    {
        $from = self::display($start, $precision);
        $to = $end ? self::display($end, $precision) : ($start ? $present : '');

        return trim($from.($from !== '' && $to !== '' ? ' – ' : '').$to);
    }

    /**
     * Parse «1405/07/18 14:30» (time optional, default 00:00) in the display
     * timezone and return the UTC moment.
     *
     * @throws InvalidArgumentException
     */
    public static function parseDateTime(?string $input): ?CarbonImmutable
    {
        $value = trim(Jalali::asciiDigits((string) $input));
        if ($value === '') {
            return null;
        }
        if (! preg_match('#^(\d{4}[/-]\d{1,2}[/-]\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$#', $value, $m)) {
            throw new InvalidArgumentException('format');
        }
        $parsed = self::parse($m[1]);
        $hour = isset($m[2]) ? (int) $m[2] : 0;
        $minute = isset($m[3]) ? (int) $m[3] : 0;
        if ($parsed === null || $hour > 23 || $minute > 59) {
            throw new InvalidArgumentException('range');
        }

        return CarbonImmutable::create(
            (int) $parsed[0]->format('Y'), (int) $parsed[0]->format('n'), (int) $parsed[0]->format('j'),
            $hour, $minute, 0, self::timezone()
        )->utc();
    }

    /** Form value for a UTC datetime, shown in the display timezone: «1405/07/18 14:30». */
    public static function toDateTimeInput(?DateTimeInterface $moment): string
    {
        return $moment ? Jalali::format($moment, 'Y/m/d H:i', false) : '';
    }

    private static function timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Tehran');
    }
}

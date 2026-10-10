<?php

namespace Tests\Unit;

use App\Support\Jalali;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reference pairs were cross-checked against the Python jdatetime library
 * (the full converter matched it for every day 1950–2050 during Phase 2).
 */
class JalaliTest extends TestCase
{
    public static function pairs(): array
    {
        return [
            'nowruz 1404' => [[2025, 3, 21], [1404, 1, 1]],
            'last day of leap 1403' => [[2025, 3, 20], [1403, 12, 30]],
            'y2k' => [[2000, 1, 1], [1378, 10, 11]],
            'project start' => [[2026, 10, 10], [1405, 7, 18]],
        ];
    }

    #[DataProvider('pairs')]
    public function test_round_trip(array $g, array $j): void
    {
        $this->assertSame($j, Jalali::fromGregorian(...$g));
        $this->assertSame($g, Jalali::toGregorian(...$j));
    }

    public function test_leap_years_and_esfand_length(): void
    {
        $this->assertTrue(Jalali::isLeapYear(1403));
        $this->assertFalse(Jalali::isLeapYear(1404));
        $this->assertSame(30, Jalali::monthLength(1403, 12));
        $this->assertSame(29, Jalali::monthLength(1404, 12));
    }

    public function test_formatting_uses_tehran_time_and_persian_digits(): void
    {
        // 21:00 UTC is 00:30 the next day in Tehran (UTC+03:30).
        $moment = CarbonImmutable::parse('2026-10-09 21:00:00', 'UTC');

        $this->assertSame('۱۴۰۵/۰۷/۱۸ ۰۰:۳۰', Jalali::format($moment, 'Y/m/d H:i'));
        $this->assertSame('۱۸ مهر ۱۴۰۵', Jalali::format($moment));
        $this->assertSame('', Jalali::format(null));
    }

    public function test_number_formatting(): void
    {
        $this->assertSame('۷٬۵۰۰٬۰۰۰', Jalali::number(7500000));
        $this->assertSame('123', Jalali::asciiDigits('۱۲۳'));
    }
}

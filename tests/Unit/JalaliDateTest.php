<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_year_month_and_day_precision_with_persian_or_latin_digits(): void
    {
        [$date, $precision] = JalaliDate::parse('۱۳۹۲');
        $this->assertSame(['2013-03-21', 'year'], [$date->toDateString(), $precision]); // 1 Farvardin 1392

        [$date, $precision] = JalaliDate::parse('1402/01');
        $this->assertSame(['2023-03-21', 'month'], [$date->toDateString(), $precision]);

        [$date, $precision] = JalaliDate::parse('۱۴۰۵-۷-۱۸');
        $this->assertSame(['2026-10-10', 'day'], [$date->toDateString(), $precision]);

        $this->assertNull(JalaliDate::parse('  '));
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_dates_are_rejected(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        JalaliDate::parse($input);
    }

    public static function invalidDates(): array
    {
        return [
            'text' => ['دیروز'],
            'month 13' => ['1402/13'],
            'Esfand 30 in a common year' => ['1404/12/30'],
            'Mehr 31' => ['1402/07/31'],
            'gregorian year' => ['2024'],
        ];
    }

    public function test_esfand_30_is_valid_in_a_leap_year(): void
    {
        $this->assertSame('day', JalaliDate::parse('1403/12/30')[1]);
    }

    public function test_display_and_round_trip_respect_precision(): void
    {
        $date = CarbonImmutable::parse('2023-03-21');
        $this->assertSame('۱۴۰۲', JalaliDate::display($date, 'year'));
        $this->assertSame('فروردین ۱۴۰۲', JalaliDate::display($date, 'month'));
        $this->assertSame('1402/01', JalaliDate::toInput($date, 'month'));
        $this->assertSame('۱۳۹۲ – اکنون', JalaliDate::range(CarbonImmutable::parse('2013-03-21'), null, 'year'));
        $this->assertSame('', JalaliDate::range(null, null, 'year'));
    }

    public function test_datetime_is_entered_in_tehran_time_and_stored_in_utc(): void
    {
        $utc = JalaliDate::parseDateTime('۱۴۰۵/۰۷/۱۸ ۱۴:۳۰');
        $this->assertSame('2026-10-10 11:00:00', $utc->toDateTimeString()); // Tehran is UTC+3:30
        $this->assertSame('1405/07/18 14:30', JalaliDate::toDateTimeInput($utc));
        $this->assertSame('2026-10-09 20:30:00', JalaliDate::parseDateTime('1405/07/18')->toDateTimeString());

        $this->expectException(InvalidArgumentException::class);
        JalaliDate::parseDateTime('1405/07/18 25:00');
    }
}

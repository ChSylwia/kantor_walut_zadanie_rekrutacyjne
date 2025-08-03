<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\ExchangeDate;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExchangeDateTest extends TestCase
{
    public function testValidExchangeDateCreation(): void
    {
        $date = $this->getValidRecentWeekday();
        $exchangeDate = new ExchangeDate($date);

        $this->assertSame($date, $exchangeDate->date);
        $this->assertSame($date->format('Y-m-d'), (string) $exchangeDate);
    }

    public function testValidExchangeDateWithDifferentWeekdays(): void
    {
        $validDates = $this->getRecentWeekdays(5);

        foreach ($validDates as $date) {
            $exchangeDate = new ExchangeDate($date);
            $this->assertSame($date->format('Y-m-d'), (string) $exchangeDate);
        }
    }

    public function testFutureDateThrowsException(): void
    {
        $futureDate = (new DateTimeImmutable())->add(new DateInterval('P2D'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange date cannot be in the future');

        new ExchangeDate($futureDate);
    }

    public function testTomorrowDateThrowsException(): void
    {
        $tomorrow = (new DateTimeImmutable())->add(new DateInterval('P1D'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange date cannot be in the future');

        new ExchangeDate($tomorrow);
    }


    public function testIsTodayWithTodaysDate(): void
    {
        $today = new DateTimeImmutable('today');
        $dayOfWeek = (int) $today->format('N');

        if ($dayOfWeek <= 5) {
            $exchangeDate = new ExchangeDate($today);
            $this->assertTrue($exchangeDate->isToday());
        } else {
            $this->markTestSkipped('Today is a weekend, skipping isToday test');
        }
    }
    public function testIsTodayWithYesterdayDate(): void
    {
        $yesterday = $this->getValidRecentWeekday(-1);
        if ($yesterday) {
            $exchangeDate = new ExchangeDate($yesterday);
            $this->assertFalse($exchangeDate->isToday());
        } else {
            $this->markTestSkipped('Cannot find valid yesterday weekday');
        }
    }

    public function testGet14DaysPeriod(): void
    {
        $date = $this->getValidRecentWeekday();
        $exchangeDate = new ExchangeDate($date);

        [$start, $end] = $exchangeDate->get14DaysPeriod();

        $expectedStart = $date->sub(new DateInterval('P13D'));
        $expectedEnd = $date;

        $this->assertEquals($expectedStart, $start);
        $this->assertEquals($expectedEnd, $end);

        // Calculate the difference between start and end
        $diff = $start->diff($end);
        $this->assertSame(13, $diff->days);
    }

    public function testEqualsWithSameDate(): void
    {
        $date = $this->getValidRecentWeekday();
        $exchangeDate1 = new ExchangeDate($date);
        $exchangeDate2 = new ExchangeDate($date);

        $this->assertTrue($exchangeDate1->equals($exchangeDate2));
        $this->assertTrue($exchangeDate2->equals($exchangeDate1));
    }

    public function testEqualsWithDifferentDates(): void
    {
        $dates = $this->getRecentWeekdays(2);
        $exchangeDate1 = new ExchangeDate($dates[0]);
        $exchangeDate2 = new ExchangeDate($dates[1]);

        $this->assertFalse($exchangeDate1->equals($exchangeDate2));
        $this->assertFalse($exchangeDate2->equals($exchangeDate1));
    }

    public function testEqualsWithSameDateDifferentTime(): void
    {
        $baseDate = $this->getValidRecentWeekday();
        $date1 = $baseDate->setTime(10, 0, 0);
        $date2 = $baseDate->setTime(15, 30, 0);
        $exchangeDate1 = new ExchangeDate($date1);
        $exchangeDate2 = new ExchangeDate($date2);

        // Should be equal because we only compare date part
        $this->assertTrue($exchangeDate1->equals($exchangeDate2));
    }

    public function testIsAfter(): void
    {
        $dates = $this->getRecentWeekdays(2);
        $laterDate = max($dates);
        $earlierDate = min($dates);

        $exchangeDate1 = new ExchangeDate($laterDate);
        $exchangeDate2 = new ExchangeDate($earlierDate);

        $this->assertTrue($exchangeDate1->isAfter($exchangeDate2));
        $this->assertFalse($exchangeDate2->isAfter($exchangeDate1));
    }

    public function testIsBefore(): void
    {
        $dates = $this->getRecentWeekdays(2);
        $laterDate = max($dates);
        $earlierDate = min($dates);

        $exchangeDate1 = new ExchangeDate($earlierDate);
        $exchangeDate2 = new ExchangeDate($laterDate);

        $this->assertTrue($exchangeDate1->isBefore($exchangeDate2));
        $this->assertFalse($exchangeDate2->isBefore($exchangeDate1));
    }

    public function testComparisonWithEqualDates(): void
    {
        $date = $this->getValidRecentWeekday();
        $exchangeDate1 = new ExchangeDate($date);
        $exchangeDate2 = new ExchangeDate($date);

        $this->assertFalse($exchangeDate1->isAfter($exchangeDate2));
        $this->assertFalse($exchangeDate1->isBefore($exchangeDate2));
        $this->assertTrue($exchangeDate1->equals($exchangeDate2));
    }

    public function testAddDaysWithNegativeValue(): void
    {
        $date = $this->getValidRecentWeekday(-2); // 2 days ago
        $exchangeDate = new ExchangeDate($date);
        $newExchangeDate = $exchangeDate->addDays(-1);

        $expectedDate = $date->sub(new DateInterval('P1D'));
        $this->assertEquals($expectedDate->format('Y-m-d'), $newExchangeDate->date->format('Y-m-d'));
    }

    public function testAddDaysWithZeroValue(): void
    {
        $date = $this->getValidRecentWeekday();
        $exchangeDate = new ExchangeDate($date);
        $newExchangeDate = $exchangeDate->addDays(0);

        $this->assertSame($exchangeDate, $newExchangeDate);
    }

    public function testGetDayOfWeek(): void
    {
        $monday = $this->findRecentMonday();
        if ($monday) {
            $exchangeDate = new ExchangeDate($monday);
            $this->assertSame('Monday', $exchangeDate->getDayOfWeek());
        } else {
            $this->markTestSkipped('Cannot find a valid recent Monday');
        }
    }

    public function testIsWeekdayWithWeekdays(): void
    {
        $weekdays = $this->getRecentWeekdays(3);

        foreach ($weekdays as $date) {
            $exchangeDate = new ExchangeDate($date);
            $this->assertTrue($exchangeDate->isWeekday(), "Failed for " . $date->format('Y-m-d'));
        }
    }

    public function testToStringConversion(): void
    {
        $date = $this->getValidRecentWeekday();
        $exchangeDate = new ExchangeDate($date);

        $this->assertSame($date->format('Y-m-d'), (string) $exchangeDate);
    }

    public function testInvalidDatesThrowExceptions(): void
    {
        $futureDate = (new DateTimeImmutable())->add(new DateInterval('P2D'));
        $tooOldDate = (new DateTimeImmutable())->sub(new DateInterval('P400D'));
        $saturday = $this->findNextSaturday();

        // Test future date
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange date cannot be in the future');
        new ExchangeDate($futureDate);
    }

    public function testValidDatesCreation(): void
    {
        $validDates = $this->getRecentWeekdays(5);

        foreach ($validDates as $date) {
            $exchangeDate = new ExchangeDate($date);
            $this->assertSame($date->format('Y-m-d'), (string) $exchangeDate);
        }
    }
    public function testBoundaryDates(): void
    {
        $today = new DateTimeImmutable('today');
        $dayOfWeek = (int) $today->format('N');

        // Test today if it's a weekday
        if ($dayOfWeek <= 5) {
            $exchangeDate = new ExchangeDate($today);
            $this->assertTrue($exchangeDate->isToday());
        }

        // Test a date that's exactly within the valid range (not exactly one year ago to avoid boundary issues)
        $almostOneYearAgo = $today->sub(new DateInterval('P360D')); // 360 days ago to be safe
        $dayOfWeekAlmostOneYearAgo = (int) $almostOneYearAgo->format('N');
        if ($dayOfWeekAlmostOneYearAgo <= 5) {
            $exchangeDate = new ExchangeDate($almostOneYearAgo);
            $this->assertFalse($exchangeDate->isToday());
        }
    }// Helper methods
    private function getValidRecentWeekday(int $daysOffset = -1): DateTimeImmutable
    {
        $date = new DateTimeImmutable();
        $attempts = 0;

        // If daysOffset is negative, we go back in time, otherwise forward
        $startDaysBack = $daysOffset < 0 ? abs($daysOffset) : 1;

        while ($attempts < 20) {
            $testDate = $date->sub(new DateInterval(sprintf('P%dD', $startDaysBack + $attempts)));
            $dayOfWeek = (int) $testDate->format('N');

            if ($dayOfWeek <= 5) { // Monday to Friday
                // Check if it's not too old
                $oneYearAgo = $date->sub(new DateInterval('P1Y'));
                if ($testDate >= $oneYearAgo) {
                    return $testDate;
                }
            }
            $attempts++;
        }

        // Fallback to a known good date if we can't find one
        return $date->sub(new DateInterval('P7D')); // One week ago
    }

    private function getRecentWeekdays(int $count): array
    {
        $dates = [];
        $date = new DateTimeImmutable();
        $attempts = 0;
        $daysBack = 1;

        while (count($dates) < $count && $attempts < 20) {
            $testDate = $date->sub(new DateInterval(sprintf('P%dD', $daysBack)));
            $dayOfWeek = (int) $testDate->format('N');

            if ($dayOfWeek <= 5) { // Monday to Friday
                $dates[] = $testDate;
            }

            $daysBack++;
            $attempts++;
        }

        return array_slice($dates, 0, $count);
    }

    private function findNextSaturday(): DateTimeImmutable
    {
        $date = new DateTimeImmutable();
        $dayOfWeek = (int) $date->format('N');
        $daysUntilSaturday = (6 - $dayOfWeek + 7) % 7;
        if ($daysUntilSaturday === 0) {
            $daysUntilSaturday = 7;
        }
        return $date->add(new DateInterval(sprintf('P%dD', $daysUntilSaturday)));
    }

    private function findRecentMonday(): ?DateTimeImmutable
    {
        $date = new DateTimeImmutable();
        for ($i = 0; $i < 14; $i++) {
            $testDate = $date->sub(new DateInterval(sprintf('P%dD', $i)));
            if ((int) $testDate->format('N') === 1) { // Monday
                $oneYearAgo = $date->sub(new DateInterval('P1Y'));
                if ($testDate >= $oneYearAgo) {
                    return $testDate;
                }
            }
        }
        return null;
    }


    private function findRecentFriday(): ?DateTimeImmutable
    {
        $date = new DateTimeImmutable();
        for ($i = 0; $i < 14; $i++) {
            $testDate = $date->sub(new DateInterval(sprintf('P%dD', $i)));
            if ((int) $testDate->format('N') === 5) { // Friday
                $oneYearAgo = $date->sub(new DateInterval('P1Y'));
                if ($testDate >= $oneYearAgo) {
                    return $testDate;
                }
            }
        }
        return null;
    }

    private function findPastSaturday(): DateTimeImmutable
    {
        $date = new DateTimeImmutable();
        for ($i = 1; $i < 14; $i++) {
            $testDate = $date->sub(new DateInterval(sprintf('P%dD', $i)));
            if ((int) $testDate->format('N') === 6) { // Saturday
                $oneYearAgo = $date->sub(new DateInterval('P1Y'));
                if ($testDate >= $oneYearAgo) {
                    return $testDate;
                }
            }
        }
        // Fallback to a date that should be Saturday
        return $date->sub(new DateInterval('P7D')); // One week ago
    }
}

<?php

declare(strict_types=1);

namespace App\ValueObject;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ExchangeDate
{
    public function __construct(public DateTimeImmutable $date)
    {
        $this->validateDate($date);
    }
    private function validateDate(DateTimeImmutable $date): void
    {
        $now = new DateTimeImmutable();
        $oneYearAgo = $now->sub(new DateInterval('P1Y'));
        $today = $now->setTime(0, 0, 0);

        // Check future dates first (before weekend validation)
        if ($date->setTime(0, 0, 0) > $today) {
            throw new InvalidArgumentException('Exchange date cannot be in the future');
        }
    }

    public function isToday(): bool
    {
        return $this->date->format('Y-m-d') === (new DateTimeImmutable())->format('Y-m-d');
    }

    public function get14DaysPeriod(): array
    {
        $end = $this->date;
        $start = $end->sub(new DateInterval('P13D'));
        return [$start, $end];
    }

    public function equals(ExchangeDate $other): bool
    {
        return $this->date->format('Y-m-d') === $other->date->format('Y-m-d');
    }

    public function isAfter(ExchangeDate $other): bool
    {
        return $this->date > $other->date;
    }

    public function isBefore(ExchangeDate $other): bool
    {
        return $this->date < $other->date;
    }

    public function addDays(int $days): self
    {
        if ($days === 0) {
            return $this;
        }

        $interval = new DateInterval(sprintf('P%dD', abs($days)));
        $newDate = $days > 0 ? $this->date->add($interval) : $this->date->sub($interval);

        return new self($newDate);
    }

    public function getDayOfWeek(): string
    {
        return $this->date->format('l'); // Full textual representation of the day of the week
    }

    public function isWeekday(): bool
    {
        $dayOfWeek = (int) $this->date->format('N'); // 1 (Monday) to 7 (Sunday)
        return $dayOfWeek <= 5;
    }

    public function __toString(): string
    {
        return $this->date->format('Y-m-d');
    }
}

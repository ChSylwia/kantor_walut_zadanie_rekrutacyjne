<?php

declare(strict_types=1);

namespace App\ValueObject;

use InvalidArgumentException;

final readonly class Rate
{
    private const PRECISION = 8; // Number of decimal places for calculations

    public function __construct(public float $value)
    {
        $this->validateRate($value);
    }

    private function validateRate(float $value): void
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Rate must be a finite number');
        }

        if ($value <= 0) {
            throw new InvalidArgumentException('Rate must be positive');
        }
    }

    public function addSpread(float $spread): self
    {
        if (!is_finite($spread)) {
            throw new InvalidArgumentException('Spread must be a finite number');
        }

        // Use regular float arithmetic instead of bcadd for compatibility
        $newValue = $this->value + $spread;

        return new self($newValue);
    }

    public function multiplyBy(float $multiplier): self
    {
        if (!is_finite($multiplier)) {
            throw new InvalidArgumentException('Multiplier must be a finite number');
        }

        if ($multiplier <= 0) {
            throw new InvalidArgumentException('Multiplier must be positive');
        }

        // Use regular float arithmetic instead of bcmul for compatibility
        $newValue = $this->value * $multiplier;

        return new self($newValue);
    }

    public function equals(Rate $other): bool
    {
        // Use epsilon comparison for float precision
        $epsilon = 1e-8; // Same precision as PRECISION constant
        return abs($this->value - $other->value) < $epsilon;
    }

    public function isGreaterThan(Rate $other): bool
    {
        $epsilon = 1e-8;
        return ($this->value - $other->value) > $epsilon;
    }

    public function isLessThan(Rate $other): bool
    {
        $epsilon = 1e-8;
        return ($other->value - $this->value) > $epsilon;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

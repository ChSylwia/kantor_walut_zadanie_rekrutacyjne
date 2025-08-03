<?php

declare(strict_types=1);

namespace App\ValueObject;

use InvalidArgumentException;

final readonly class CurrencyCode
{
    public function __construct(public string $value)
    {
        $this->validateCurrencyCode($value);
    }

    private function validateCurrencyCode(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Currency code cannot be empty');
        }

        if (strlen($value) !== 3) {
            throw new InvalidArgumentException('Currency code must be exactly 3 characters long');
        }

        if (!ctype_alpha($value)) {
            throw new InvalidArgumentException('Currency code must contain only letters');
        }

        if ($value !== strtoupper($value)) {
            throw new InvalidArgumentException('Currency code must be uppercase');
        }
    }

    public function equals(CurrencyCode $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

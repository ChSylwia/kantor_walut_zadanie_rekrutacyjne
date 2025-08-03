<?php

declare(strict_types=1);

namespace App\Dto;

use App\ValueObject\CurrencyCode;
use App\ValueObject\ExchangeDate;
use App\ValueObject\Rate;
use DateTimeImmutable;
use JsonSerializable;

final readonly class ExchangeRateWithSpreadsDto implements JsonSerializable
{
    public function __construct(
        public CurrencyCode $codeIso,
        public Rate $currentMid,
        public ?Rate $buyRate,
        public ?Rate $sellRate,
        public ExchangeDate $effectiveDate,
        public string $name,
        public Rate $previousMid,
        public DateTimeImmutable $updatedAt
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'codeIso' => $this->codeIso->value,
            'name' => $this->name,
            'currentMid' => $this->currentMid->value,
            'buyRate' => $this->buyRate?->value,
            'sellRate' => $this->sellRate?->value,
            'previousMid' => $this->previousMid->value,
            'effectiveDate' => $this->effectiveDate->date->format('Y-m-d'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}

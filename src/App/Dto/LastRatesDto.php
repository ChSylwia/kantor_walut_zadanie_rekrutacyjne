<?php

declare(strict_types=1);

namespace App\Dto;

use JsonSerializable;

class LastRatesDto implements JsonSerializable
{
    /**
     * @param LastRateEntryDto[] $rates
     */
    public function __construct(
        public readonly array $rates
    ) {
    }

    public function jsonSerialize(): array
    {
        return ['rates' => $this->rates];
    }
}

<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

use App\Dto\ExchangeRateWithSpreadsDto;
use App\Service\ExchangeRates\SpreadCalculator;

final readonly class ExchangeRateWithSpreadsProvider
{
    public function __construct(
        private AirtableLastExchangesProvider $airtableProvider,
        private SpreadCalculator $spreadCalculator
    ) {
    }

    /**
     * @return ExchangeRateWithSpreadsDto[]
     */
    public function getExchangeRatesWithSpreads(): array
    {
        $lastRatesDto = $this->airtableProvider->getLastRates();

        $ratesWithSpreads = [];

        foreach ($lastRatesDto->rates as $rateEntry) {
            $buyRate = $this->spreadCalculator->calculateBuyRate($rateEntry->codeIso, $rateEntry->currentMid);
            $sellRate = $this->spreadCalculator->calculateSellRate($rateEntry->codeIso, $rateEntry->currentMid);

            $ratesWithSpreads[] = new ExchangeRateWithSpreadsDto(
                codeIso: $rateEntry->codeIso,
                currentMid: $rateEntry->currentMid,
                buyRate: $buyRate,
                sellRate: $sellRate,
                effectiveDate: $rateEntry->effectiveDate,
                name: $rateEntry->name,
                previousMid: $rateEntry->previousMid,
                updatedAt: $rateEntry->updatedAt
            );
        }

        return $ratesWithSpreads;
    }
}

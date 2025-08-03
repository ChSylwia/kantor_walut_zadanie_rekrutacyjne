<?php

declare(strict_types=1);

namespace App\Service\ExchangeRates;

use App\ValueObject\CurrencyCode;
use App\ValueObject\Rate;

class SpreadCalculator
{
    public function __construct(
        private readonly ExchangeRatesConfig $exchangeRatesConfig
    ) {
    }

    public function calculateBuyRate(CurrencyCode $currency, Rate $baseRate): ?Rate
    {
        $spread = $this->exchangeRatesConfig->getBuySpread($currency);

        if ($spread === null) {
            return null;
        }

        return $baseRate->addSpread($spread);
    }

    public function calculateSellRate(CurrencyCode $currency, Rate $baseRate): ?Rate
    {
        $spread = $this->exchangeRatesConfig->getSellSpread($currency);

        if ($spread === null) {
            return null;
        }

        return $baseRate->addSpread($spread);
    }
}

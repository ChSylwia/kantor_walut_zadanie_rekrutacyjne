<?php

declare(strict_types=1);

namespace App\Service\CurrencyCalculator;

use App\Service\ExchangeRate\AirtableLastExchangesProvider;
use App\Service\ExchangeRates\ExchangeRatesConfig;
use App\Service\ExchangeRates\SpreadCalculator;
use App\ValueObject\CurrencyCode;
use App\ValueObject\Rate;
use InvalidArgumentException;

final readonly class CurrencyCalculatorService
{
    public function __construct(
        private AirtableLastExchangesProvider $airtableProvider,
        private SpreadCalculator $spreadCalculator,
        private ExchangeRatesConfig $exchangeRatesConfig
    ) {
    }
    public function calculate(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        string $operationType = 'mid'
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        $fromCode = new CurrencyCode(strtoupper($fromCurrency));
        $toCode = new CurrencyCode(strtoupper($toCurrency));        // Validate operation availability for foreign currencies
        $this->validateOperationAvailability($fromCode, $toCode, $operationType);

        // For PLN to PLN - return original amount
        if ($fromCode->value === 'PLN' && $toCode->value === 'PLN') {
            return [
                'result' => $amount,
                'fromCurrency' => $fromCode->value,
                'toCurrency' => $toCode->value,
                'amount' => $amount,
                'rate' => 1.0,
                'operationType' => $operationType
            ];
        }

        $lastRatesDto = $this->airtableProvider->getLastRates();
        $rates = [];
        foreach ($lastRatesDto->rates as $rate) {
            $rates[$rate->codeIso->value] = $rate;
        }

        $result = null;
        $usedRate = null;

        if ($fromCode->value === 'PLN') {
            // PLN -> waluta obca (client buys foreign currency)
            if (!isset($rates[$toCode->value])) {
                throw new InvalidArgumentException("Currency {$toCode->value} not available");
            }

            $rateEntry = $rates[$toCode->value];
            // When client buys foreign currency with PLN, use sell rate 
            $rateType = $operationType === 'buy' ? 'sell' : ($operationType === 'sell' ? 'buy' : 'mid');
            $rate = $this->getRate($rateEntry, $toCode, $rateType, 'sell');

            // PLN / currency rate = amount in foreign currency
            $result = $amount / $rate->value;
            $usedRate = $rate->value;

        } elseif ($toCode->value === 'PLN') {
            // Waluta obca -> PLN (client sells foreign currency)
            if (!isset($rates[$fromCode->value])) {
                throw new InvalidArgumentException("Currency {$fromCode->value} not available");
            }

            $rateEntry = $rates[$fromCode->value];
            // When client sells foreign currency for PLN, use buy rate
            $rateType = $operationType === 'buy' ? 'sell' : ($operationType === 'sell' ? 'buy' : 'mid');
            $rate = $this->getRate($rateEntry, $fromCode, $rateType, 'buy');

            // currency amount * rate = PLN
            $result = $amount * $rate->value;
            $usedRate = $rate->value;

        } else {
            // Waluta obca -> waluta obca (cross-currency)
            if (!isset($rates[$fromCode->value])) {
                throw new InvalidArgumentException("Currency {$fromCode->value} not available");
            }
            if (!isset($rates[$toCode->value])) {
                throw new InvalidArgumentException("Currency {$toCode->value} not available");
            }

            $fromRateEntry = $rates[$fromCode->value];
            $toRateEntry = $rates[$toCode->value];

            // For cross-currency: sell source currency (get PLN), then buy target currency (with PLN)
            $fromRateType = $operationType === 'buy' ? 'sell' : ($operationType === 'sell' ? 'buy' : 'mid');
            $toRateType = $operationType === 'buy' ? 'sell' : ($operationType === 'sell' ? 'buy' : 'mid');

            $fromRate = $this->getRate($fromRateEntry, $fromCode, $fromRateType, 'buy');
            $toRate = $this->getRate($toRateEntry, $toCode, $toRateType, 'sell');

            // kwota * kurs_from / kurs_to
            $plnAmount = $amount * $fromRate->value;
            $result = $plnAmount / $toRate->value;
            $usedRate = $fromRate->value / $toRate->value;
        }        // Apply rounding according to exchange office practices
        $finalResult = $this->applyExchangeRounding($result, $fromCode->value, $toCode->value, $operationType);

        return [
            'result' => $finalResult,
            'fromCurrency' => $fromCode->value,
            'toCurrency' => $toCode->value,
            'amount' => $amount,
            'rate' => $usedRate,
            'operationType' => $operationType
        ];
    }

    private function getRate($rateEntry, CurrencyCode $currencyCode, string $operationType, string $direction): Rate
    {
        return match ($operationType) {
            'buy' => $this->spreadCalculator->calculateBuyRate($currencyCode, $rateEntry->currentMid) ?? $rateEntry->currentMid,
            'sell' => $this->spreadCalculator->calculateSellRate($currencyCode, $rateEntry->currentMid) ?? $rateEntry->currentMid,
            default => $rateEntry->currentMid
        };
    }

    private function validateOperationAvailability(
        CurrencyCode $fromCode,
        CurrencyCode $toCode,
        string $operationType
    ): void {
        // For 'mid' operation there are no restrictions
        if ($operationType === 'mid') {
            return;
        }

        if ($fromCode->value === 'PLN' && $toCode->value !== 'PLN') {
            // PLN → foreign currency (client buys foreign currency)
            if ($operationType === 'buy' && $this->exchangeRatesConfig->getBuySpread($toCode) === null) {
                throw new InvalidArgumentException(
                    "Buy operation not available for currency {$toCode->value}"
                );
            }
            if ($operationType === 'sell' && $this->exchangeRatesConfig->getSellSpread($toCode) === null) {
                throw new InvalidArgumentException(
                    "Sell operation not available for currency {$toCode->value}"
                );
            }
        } elseif ($fromCode->value !== 'PLN' && $toCode->value === 'PLN') {
            // Foreign currency → PLN (client sells foreign currency)
            if ($operationType === 'buy' && $this->exchangeRatesConfig->getBuySpread($fromCode) === null) {
                throw new InvalidArgumentException(
                    "Buy operation not available for currency {$fromCode->value}"
                );
            }
            if ($operationType === 'sell' && $this->exchangeRatesConfig->getSellSpread($fromCode) === null) {
                throw new InvalidArgumentException(
                    "Sell operation not available for currency {$fromCode->value}"
                );
            }
        } elseif ($fromCode->value !== 'PLN' && $toCode->value !== 'PLN') {
            // Cross-currency exchange
            if ($operationType === 'buy') {
                // For buy operation, check that both currencies support buy operations
                if ($this->exchangeRatesConfig->getBuySpread($fromCode) === null) {
                    throw new InvalidArgumentException(
                        "Buy operation not available for currency {$fromCode->value}"
                    );
                }
                if ($this->exchangeRatesConfig->getBuySpread($toCode) === null) {
                    throw new InvalidArgumentException(
                        "Buy operation not available for currency {$toCode->value}"
                    );
                }
            }
            if ($operationType === 'sell') {
                // For sell operation, check that both currencies support sell operations
                if ($this->exchangeRatesConfig->getSellSpread($fromCode) === null) {
                    throw new InvalidArgumentException(
                        "Sell operation not available for currency {$fromCode->value}"
                    );
                }
                if ($this->exchangeRatesConfig->getSellSpread($toCode) === null) {
                    throw new InvalidArgumentException(
                        "Sell operation not available for currency {$toCode->value}"
                    );
                }
            }
        }
    }    /**
         * Rounds result according to exchange office practices and EU law
         * 
         * @param float $result Calculation result
         * @param string $fromCurrency Source currency
         * @param string $toCurrency Target currency  
         * @param string $operationType Operation type (mid/buy/sell)
         * @return float Properly rounded result
         */
    private function applyExchangeRounding(
        float $result,
        string $fromCurrency,
        string $toCurrency,
        string $operationType
    ): float {
        // Determine the number of decimal places based on the target currency
        $decimalPlaces = ($toCurrency === 'PLN') ? 2 : 2; // Most currencies use 2 decimal places
        $multiplier = pow(10, $decimalPlaces);

        if ($operationType === 'buy') {
            // Client buys foreign currency → exchange office sells → client receives "reduced" amount
            return floor($result * $multiplier) / $multiplier;
        }

        if ($operationType === 'sell') {
            // Client sells foreign currency → exchange office buys → exchange office pays more PLN
            return ceil($result * $multiplier) / $multiplier;
        }

        // Mid rate → standard mathematical rounding (half-up)
        return round($result, $decimalPlaces, PHP_ROUND_HALF_UP);
    }
}

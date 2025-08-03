<?php

declare(strict_types=1);

namespace App\Service\Nbp;

use App\Dto\LastRateEntryDto;
use App\Dto\LastRatesDto;
use App\Service\ExchangeRates\ExchangeRatesConfig;
use App\ValueObject\CurrencyCode;
use App\ValueObject\ExchangeDate;
use App\ValueObject\Rate;
use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use JsonException;

final readonly class NbpLastRatesProvider
{
    public function __construct(
        private NbpClient $nbpClient,
        private ExchangeRatesConfig $exchangeRatesConfig
    ) {
    }/**
     * @throws GuzzleException
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function getLastRatesForAllCurrencies(): LastRatesDto
    {
        $tablesData = $this->nbpClient->getLastExchangeRatesTables(2);

        if (empty($tablesData) || count($tablesData) < 2) {
            throw new InvalidArgumentException('Insufficient exchange rate tables data received');
        }

        // Previous table (older)
        $previousTable = $tablesData[0];
        // Current table (newer)
        $currentTable = $tablesData[1];

        $rateEntries = [];
        foreach ($currentTable['rates'] as $currentRate) {
            $currencyCode = new CurrencyCode($currentRate['code']);

            // Skip currencies not defined in configuration
            if (!$this->exchangeRatesConfig->isCurrencyAllowed($currencyCode)) {
                continue;
            }

            // Find corresponding previous rate
            $previousRate = null;
            foreach ($previousTable['rates'] as $prevRate) {
                if ($prevRate['code'] === $currentRate['code']) {
                    $previousRate = $prevRate;
                    break;
                }
            }

            // Skip if no previous rate found
            if (!$previousRate) {
                continue;
            }

            $rateEntries[] = new LastRateEntryDto(
                codeIso: $currencyCode,
                currentMid: new Rate($currentRate['mid']),
                effectiveDate: new ExchangeDate(new DateTimeImmutable($currentTable['effectiveDate'])),
                name: $currentRate['currency'],
                previousMid: new Rate($previousRate['mid']),
                updatedAt: new DateTimeImmutable()
            );
        }

        return new LastRatesDto($rateEntries);
    }
}
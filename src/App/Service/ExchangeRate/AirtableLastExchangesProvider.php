<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

use App\Dto\LastRatesDto;
use App\Dto\LastRateEntryDto;
use App\Service\Airtable\AirtableClient;
use App\Service\ExchangeRates\ExchangeRatesConfig;
use App\ValueObject\CurrencyCode;
use App\ValueObject\ExchangeDate;
use App\ValueObject\Rate;
use DateTimeImmutable;
use InvalidArgumentException;

class AirtableLastExchangesProvider
{
    private const TABLE_NAME = 'last_rates';

    public function __construct(
        private readonly AirtableClient $airtableClient,
        private readonly ExchangeRatesConfig $exchangeRatesConfig
    ) {
    }
    public function getLastRates(): LastRatesDto
    {
        $records = $this->airtableClient->getAllRecords(self::TABLE_NAME);

        $rates = [];
        foreach ($records as $record) {
            $fields = $record['fields'] ?? [];

            $currencyCode = new CurrencyCode($fields['code_iso']);

            // Skip currencies not defined in configuration
            if (!$this->exchangeRatesConfig->isCurrencyAllowed($currencyCode)) {
                continue;
            }

            $currentMid = new Rate((float) $fields['current_mid']);
            $rates[] = new LastRateEntryDto(
                codeIso: $currencyCode,
                currentMid: $currentMid,
                effectiveDate: new ExchangeDate(new DateTimeImmutable($fields['effective_date'])),
                name: $fields['name'],
                previousMid: new Rate((float) $fields['previous_mid']),
                updatedAt: new DateTimeImmutable($fields['updated_at'])
            );
        }

        return new LastRatesDto($rates);
    }
}

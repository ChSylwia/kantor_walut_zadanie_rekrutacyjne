<?php

declare(strict_types=1);

namespace App\Service\LastRatesUpdater;

use App\Dto\LastRateEntryDto;
use App\Service\Airtable\AirtableClient;
use App\Service\Nbp\NbpLastRatesProvider;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use JsonException;
use DateTimeImmutable;

final readonly class LastRatesUpdaterService
{
    private const TABLE_NAME = 'last_rates';

    public function __construct(
        private AirtableClient $airtableClient,
        private NbpLastRatesProvider $nbpLastRatesProvider
    ) {
    }    /**
         * Main method for updates – performs upsert for all rates.
         *
         * @throws GuzzleException
         * @throws JsonException
         * @throws InvalidArgumentException
         */
    public function updateRates(): array
    {
        $lastRatesDto = $this->nbpLastRatesProvider->getLastRatesForAllCurrencies();

        $recordsData = [];
        foreach ($lastRatesDto->rates as $rateEntry) {
            $recordsData[] = $this->mapDtoToRecord($rateEntry);
        }

        return $this->airtableClient->upsertTable(self::TABLE_NAME, $recordsData);
    }    /**
         * Gets exactly one record from Airtable with the highest effective_date.
         *
         * @return string|null  date in Y-m-d format or null if no records found
         */
    private function getLastEffectiveDate(): ?string
    {
        // fetch first page with one record, sorted descending
        $records = $this->airtableClient->getAllRecords(self::TABLE_NAME, [
            'pageSize' => 1,
            'sort' => [['field' => 'effective_date', 'direction' => 'desc']],
        ]);

        if (empty($records)) {
            return null;
        }

        return $records[0]['fields']['effective_date'] ?? null;
    }    /**
         * Gets exactly one record from Airtable with the highest updated_at date.
         *
         * @return string|null  date in Y-m-d H:i:s format or null if no records found
         */
    private function getLastUpdatedAt(): ?string
    {
        // fetch first page with one record, sorted descending
        $records = $this->airtableClient->getAllRecords(self::TABLE_NAME, [
            'pageSize' => 1,
            'sort' => [['field' => 'updated_at', 'direction' => 'desc']],
        ]);

        if (empty($records)) {
            return null;
        }

        return $records[0]['fields']['updated_at'] ?? null;
    }

    private function mapDtoToRecord(LastRateEntryDto $rateEntry): array
    {
        return [
            'code_iso' => $rateEntry->codeIso->value,
            'current_mid' => $rateEntry->currentMid->value,
            'effective_date' => $rateEntry->effectiveDate->date->format('Y-m-d'),
            'name' => $rateEntry->name,
            'previous_mid' => $rateEntry->previousMid->value,
            'updated_at' => $rateEntry->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}

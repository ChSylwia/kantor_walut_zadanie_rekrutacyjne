<?php

declare(strict_types=1);

namespace App\Service\Nbp;

use App\ValueObject\CurrencyCode;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

final readonly class NbpCurrencyHistoryClient
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'NBP-CurrencyHistory-Client/1.0'
            ]
        ]);
    }    /**
         * @throws GuzzleException
         * @throws JsonException
         */
    public function getCurrencyHistoryLast14Days(CurrencyCode $currencyCode, string $table = 'A', ?DateTimeImmutable $endDate = null): array
    {
        if ($endDate === null) {
            $url = "https://api.nbp.pl/api/exchangerates/rates/{$table}/{$currencyCode->value}/last/14?format=json";
            $response = $this->httpClient->get($url);
            $content = $response->getBody()->getContents();
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }        // For specified end date, fetch data iteratively, increasing the range until we get 14 days
        $allRates = [];
        $daysBack = 20; // Start from 20 days back
        $maxAttempts = 5; // Maximum 5 attempts
        $attempt = 0;

        while (count($allRates) < 14 && $attempt < $maxAttempts) {
            $startDate = $endDate->modify("-{$daysBack} days");
            $startDateString = $startDate->format('Y-m-d');
            $endDateString = $endDate->format('Y-m-d');

            $url = "https://api.nbp.pl/api/exchangerates/rates/{$table}/{$currencyCode->value}/{$startDateString}/{$endDateString}?format=json";

            $response = $this->httpClient->get($url);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (isset($data['rates'])) {
                // Filter rates not later than endDate
                $filteredRates = array_filter($data['rates'], function ($rate) use ($endDate) {
                    $rateDate = new DateTimeImmutable($rate['effectiveDate']);
                    return $rateDate <= $endDate;
                });

                // Sort by date descending
                usort($filteredRates, function ($a, $b) {
                    return $b['effectiveDate'] <=> $a['effectiveDate'];
                });

                $allRates = $filteredRates;
            }

            $daysBack += 15; // Increase range by another 15 days
            $attempt++;
        }

        // Limit to maximum 14 newest results
        $finalRates = array_slice($allRates, 0, 14);

        return [
            'table' => $data['table'] ?? $table,
            'currency' => $data['currency'] ?? $currencyCode->value,
            'code' => $data['code'] ?? $currencyCode->value,
            'rates' => $finalRates
        ];
    }
}

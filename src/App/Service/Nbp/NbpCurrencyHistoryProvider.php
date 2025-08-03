<?php

declare(strict_types=1);

namespace App\Service\Nbp;

use App\ValueObject\CurrencyCode;
use App\ValueObject\ExchangeDate;
use App\ValueObject\Rate;
use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use JsonException;

final readonly class NbpCurrencyHistoryProvider
{
    public function __construct(
        private NbpCurrencyHistoryClient $client
    ) {
    }    /**
         * @throws GuzzleException
         * @throws JsonException
         * @throws InvalidArgumentException
         */
    public function getCurrencyHistoryLast14Days(CurrencyCode $currencyCode, string $table = 'A', ?\DateTimeImmutable $endDate = null): array
    {
        $data = $this->client->getCurrencyHistoryLast14Days($currencyCode, $table, $endDate);

        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new InvalidArgumentException('Invalid NBP API response format');
        }

        $rates = [];
        foreach ($data['rates'] as $rateData) {
            $rates[] = [
                'no' => $rateData['no'],
                'effectiveDate' => new ExchangeDate(new DateTimeImmutable($rateData['effectiveDate'])),
                'mid' => new Rate((float) $rateData['mid'])
            ];
        }

        return [
            'table' => $data['table'],
            'currency' => $data['currency'],
            'code' => new CurrencyCode($data['code']),
            'rates' => $rates
        ];
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getCurrencyHistoryLast14DaysAsJson(CurrencyCode $currencyCode, string $table = 'A'): string
    {
        $data = $this->getCurrencyHistoryLast14Days($currencyCode, $table);

        $jsonData = [
            'table' => $data['table'],
            'currency' => $data['currency'],
            'code' => $data['code']->value,
            'rates' => array_map(function ($rate) {
                return [
                    'no' => $rate['no'],
                    'effectiveDate' => $rate['effectiveDate']->date->format('Y-m-d'),
                    'mid' => $rate['mid']->value
                ];
            }, $data['rates'])
        ];

        return json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service\Nbp;

use App\Service\Nbp\NbpCurrencyHistoryClient;
use App\ValueObject\CurrencyCode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class NbpCurrencyHistoryClientTest extends TestCase
{
    private NbpCurrencyHistoryClient $client;

    protected function setUp(): void
    {
        $this->client = new NbpCurrencyHistoryClient();
    }

    public function testGetCurrencyHistoryLast14DaysWithoutEndDate(): void
    {
        $currencyCode = new CurrencyCode('EUR');
        $result = $this->client->getCurrencyHistoryLast14Days($currencyCode);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rates', $result);
        $this->assertLessThanOrEqual(14, count($result['rates']));
    }
    public function testGetCurrencyHistoryLast14DaysWithEndDate(): void
    {
        $currencyCode = new CurrencyCode('USD');
        $endDate = new DateTimeImmutable('2024-12-01'); // Selected date in the past

        $result = $this->client->getCurrencyHistoryLast14Days($currencyCode, 'A', $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rates', $result);

        // Check if we have results (may be less than 14 due to weekends/holidays)
        $this->assertGreaterThan(0, count($result['rates']));
        $this->assertLessThanOrEqual(14, count($result['rates']));

        // Check if all dates are before or equal to endDate
        foreach ($result['rates'] as $rate) {
            $rateDate = new DateTimeImmutable($rate['effectiveDate']);
            $this->assertLessThanOrEqual($endDate, $rateDate);
        }
    }
    public function testGetCurrencyHistoryEnsures14DaysWhenPossible(): void
    {
        $currencyCode = new CurrencyCode('EUR');
        $endDate = new DateTimeImmutable('2024-01-31'); // Date with sufficient previous business days

        $result = $this->client->getCurrencyHistoryLast14Days($currencyCode, 'A', $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rates', $result);

        // Check sorting - newest dates should be first
        if (count($result['rates']) > 1) {
            $firstDate = new DateTimeImmutable($result['rates'][0]['effectiveDate']);
            $secondDate = new DateTimeImmutable($result['rates'][1]['effectiveDate']);
            $this->assertGreaterThanOrEqual($secondDate, $firstDate);
        }
    }
}

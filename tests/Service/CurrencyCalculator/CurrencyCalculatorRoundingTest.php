<?php

declare(strict_types=1);

namespace App\Tests\Service\CurrencyCalculator;

use App\Dto\LastRateEntryDto;
use App\Dto\LastRatesDto;
use App\Service\CurrencyCalculator\CurrencyCalculatorService;
use App\Service\ExchangeRate\AirtableLastExchangesProvider;
use App\Service\ExchangeRates\ExchangeRatesConfig;
use App\Service\ExchangeRates\SpreadCalculator;
use App\ValueObject\CurrencyCode;
use App\ValueObject\ExchangeDate;
use App\ValueObject\Rate;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CurrencyCalculatorRoundingTest extends TestCase
{
    private CurrencyCalculatorService $service;
    private AirtableLastExchangesProvider $airtableProvider;
    private SpreadCalculator $spreadCalculator;
    private ExchangeRatesConfig $exchangeRatesConfig;

    protected function setUp(): void
    {
        $this->airtableProvider = $this->createMock(AirtableLastExchangesProvider::class);
        $this->spreadCalculator = $this->createMock(SpreadCalculator::class);

        // Use real configuration from YAML file
        $configPath = __DIR__ . '/../../../config/exchange_rates.yaml';
        $this->exchangeRatesConfig = new ExchangeRatesConfig($configPath);

        $this->service = new CurrencyCalculatorService(
            $this->airtableProvider,
            $this->spreadCalculator,
            $this->exchangeRatesConfig
        );
    }

    public function testBuyForeignCurrencyUsesFloor(): void
    {
        // Client buys USD with PLN → receives less USD (floor rounding)
        $rateEntry = new LastRateEntryDto(
            codeIso: new CurrencyCode('USD'),
            currentMid: new Rate(4.0),
            effectiveDate: new ExchangeDate(new DateTimeImmutable()),
            name: 'US Dollar',
            previousMid: new Rate(4.0),
            updatedAt: new DateTimeImmutable()
        );

        $this->setupMocks([$rateEntry]);
        $this->setupSpreadMocks();

        // 100 PLN / 4.11 (sell rate) = 24.3309... → 24.33 (floor)
        $result = $this->service->calculate(100.0, 'PLN', 'USD', 'buy');
        $this->assertEquals(24.33, $result['result']);
    }


    private function setupMocks(array $rateEntries): void
    {
        $lastRatesDto = new LastRatesDto($rateEntries);
        $this->airtableProvider->method('getLastRates')->willReturn($lastRatesDto);
    }

    private function setupSpreadMocks(): void
    {
        $this->spreadCalculator->method('calculateBuyRate')
            ->willReturn(new Rate(3.85));
        $this->spreadCalculator->method('calculateSellRate')
            ->willReturn(new Rate(4.11));
    }

    private function setupCrossCurrencySpreadMocks(): void
    {
        $this->spreadCalculator->method('calculateBuyRate')
            ->willReturnCallback(function (CurrencyCode $currency, Rate $rate) {
                if ($currency->value === 'USD')
                    return new Rate(3.85);
                if ($currency->value === 'EUR')
                    return new Rate(4.35);
                return $rate;
            });

        $this->spreadCalculator->method('calculateSellRate')
            ->willReturnCallback(function (CurrencyCode $currency, Rate $rate) {
                if ($currency->value === 'USD')
                    return new Rate(4.11);
                if ($currency->value === 'EUR')
                    return new Rate(4.61);
                return $rate;
            });
    }
}

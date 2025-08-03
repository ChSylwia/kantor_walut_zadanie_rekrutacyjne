<?php

declare(strict_types=1);

namespace App\Tests\Service\CurrencyCalculator;

use App\Service\CurrencyCalculator\CurrencyCalculatorService;
use App\Service\ExchangeRate\AirtableLastExchangesProvider;
use App\Service\ExchangeRates\ExchangeRatesConfig;
use App\Service\ExchangeRates\SpreadCalculator;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CurrencyCalculatorOperationValidationTest extends TestCase
{
    private CurrencyCalculatorService $service;
    protected function setUp(): void
    {
        // Use real configuration from YAML file
        $configPath = __DIR__ . '/../../../config/exchange_rates.yaml';
        $exchangeRatesConfig = new ExchangeRatesConfig($configPath);

        // Mock remaining services
        $airtableProvider = $this->createMock(AirtableLastExchangesProvider::class);

        // Create mock rate entries for currencies in config
        $mockRates = [
            new \App\Dto\LastRateEntryDto(
                codeIso: new \App\ValueObject\CurrencyCode('CZK'),
                currentMid: new \App\ValueObject\Rate(0.18),
                effectiveDate: new \App\ValueObject\ExchangeDate(new DateTimeImmutable()),
                name: 'Czech Koruna',
                previousMid: new \App\ValueObject\Rate(0.17),
                updatedAt: new DateTimeImmutable()
            ),
            new \App\Dto\LastRateEntryDto(
                codeIso: new \App\ValueObject\CurrencyCode('USD'),
                currentMid: new \App\ValueObject\Rate(4.0),
                effectiveDate: new \App\ValueObject\ExchangeDate(new DateTimeImmutable()),
                name: 'US Dollar',
                previousMid: new \App\ValueObject\Rate(3.9),
                updatedAt: new DateTimeImmutable()
            ),
            new \App\Dto\LastRateEntryDto(
                codeIso: new \App\ValueObject\CurrencyCode('EUR'),
                currentMid: new \App\ValueObject\Rate(4.5),
                effectiveDate: new \App\ValueObject\ExchangeDate(new DateTimeImmutable()),
                name: 'Euro',
                previousMid: new \App\ValueObject\Rate(4.4),
                updatedAt: new DateTimeImmutable()
            )
        ];

        $airtableProvider->method('getLastRates')->willReturn(new \App\Dto\LastRatesDto($mockRates));

        $spreadCalculator = $this->createMock(SpreadCalculator::class);

        $this->service = new CurrencyCalculatorService(
            $airtableProvider,
            $spreadCalculator,
            $exchangeRatesConfig
        );
    }
    public function testBuyOperationFailsForCurrencyWithoutBuySpread(): void
    {
        // CZK has only sell spread, no buy
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Buy operation not available for currency CZK');

        $this->service->calculate(100.0, 'CZK', 'PLN', 'buy');
    }
    public function testSellOperationFailsForCurrencyWithoutSellSpread(): void
    {
        // Test with currency that doesn't exist in YAML
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sell operation not available for currency GBP');

        $this->service->calculate(100.0, 'PLN', 'GBP', 'sell');
    }
    public function testBuyOperationFailsForCrossCurrencyWithoutBuySpread(): void
    {
        // CZK -> USD, where CZK has no buy spread
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Buy operation not available for currency CZK');

        $this->service->calculate(100.0, 'CZK', 'USD', 'buy');
    }
    public function testMidOperationWorksEvenWithoutSpreads(): void
    {
        // Mid operation should work even for currencies without spreads
        // Test won't throw exception at this validation stage
        try {
            $this->service->calculate(100.0, 'GBP', 'PLN', 'mid');
            $this->fail('Expected exception for missing currency data');
        } catch (InvalidArgumentException $e) {
            // We expect "Currency GBP not available" error from later validation,
            // not an operation unavailability error
            $this->assertStringContainsString('not available', $e->getMessage());
            $this->assertStringNotContainsString('operation not available', $e->getMessage());
        }
    }
}

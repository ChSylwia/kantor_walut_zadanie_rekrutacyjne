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
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CurrencyCalculatorServiceTest extends TestCase
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

    public function testCalculatePlnToPlnReturnsOriginalAmount(): void
    {
        $result = $this->service->calculate(100.0, 'PLN', 'PLN', 'mid');

        $this->assertEquals([
            'result' => 100.0,
            'fromCurrency' => 'PLN',
            'toCurrency' => 'PLN',
            'amount' => 100.0,
            'rate' => 1.0,
            'operationType' => 'mid'
        ], $result);
    }

    public function testCalculateWithNegativeAmountThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->service->calculate(-100.0, 'PLN', 'USD');
    }

    public function testCalculateWithUnavailableCurrencyThrowsException(): void
    {
        $this->airtableProvider
            ->expects($this->once())
            ->method('getLastRates')
            ->willReturn(new LastRatesDto([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency USD not available');

        $this->service->calculate(100.0, 'PLN', 'USD');
    }
    public function testCalculateWithMidOperationTypeUsesCurrentMid(): void
    {
        $rateEntry = new LastRateEntryDto(
            codeIso: new CurrencyCode('USD'),
            currentMid: new Rate(4.0),
            effectiveDate: new ExchangeDate(new DateTimeImmutable()),
            name: 'US Dollar',
            previousMid: new Rate(3.9),
            updatedAt: new DateTimeImmutable()
        );

        $lastRatesDto = new LastRatesDto([$rateEntry]);

        $this->airtableProvider
            ->expects($this->once())
            ->method('getLastRates')
            ->willReturn($lastRatesDto);

        $result = $this->service->calculate(100.0, 'USD', 'PLN', 'mid');

        $this->assertEquals('USD', $result['fromCurrency']);
        $this->assertEquals('PLN', $result['toCurrency']);
        $this->assertEquals(100.0, $result['amount']);
        $this->assertEquals(400.0, $result['result']);
        $this->assertEquals(4.0, $result['rate']);
        $this->assertEquals('mid', $result['operationType']);
    }
    public function testCalculateWithUnavailableBuyOperationThrowsException(): void
    {
        // CZK has only sell spread, no buy - this should fail
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Buy operation not available for currency CZK');

        $this->service->calculate(100.0, 'CZK', 'PLN', 'buy');
    }

    public function testCalculateWithUnavailableSellOperationThrowsException(): void
    {
        // GBP is not in config at all, so sell operation should fail
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sell operation not available for currency GBP');

        $this->service->calculate(100.0, 'PLN', 'GBP', 'sell');
    }
    public function testCalculateWithUnavailableBuyForCrossCurrencyThrowsException(): void
    {
        // Test when CZK is used in cross-currency - CZK has no buy spread
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Buy operation not available for currency CZK');

        $this->service->calculate(100.0, 'CZK', 'USD', 'buy');
    }

    public function testCalculateMidOperationWorksEvenWithoutSpreads(): void
    {
        $rateEntry = new LastRateEntryDto(
            codeIso: new CurrencyCode('GBP'),
            currentMid: new Rate(5.0),
            effectiveDate: new ExchangeDate(new DateTimeImmutable()),
            name: 'British Pound',
            previousMid: new Rate(4.9),
            updatedAt: new DateTimeImmutable()
        );

        $lastRatesDto = new LastRatesDto([$rateEntry]);

        $this->airtableProvider
            ->method('getLastRates')
            ->willReturn($lastRatesDto);

        // Mid operation does not check spread availability
        $result = $this->service->calculate(100.0, 'GBP', 'PLN', 'mid');

        $this->assertEquals('GBP', $result['fromCurrency']);
        $this->assertEquals('PLN', $result['toCurrency']);
        $this->assertEquals(500.0, $result['result']);
        $this->assertEquals('mid', $result['operationType']);
    }
}

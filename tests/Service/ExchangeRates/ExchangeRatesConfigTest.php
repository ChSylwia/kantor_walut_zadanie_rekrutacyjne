<?php

declare(strict_types=1);

namespace App\Tests\Service\ExchangeRates;

use App\Service\ExchangeRates\ExchangeRatesConfig;
use App\ValueObject\CurrencyCode;
use PHPUnit\Framework\TestCase;

class ExchangeRatesConfigTest extends TestCase
{
    private ExchangeRatesConfig $config;
    protected function setUp(): void
    {
        $configPath = __DIR__ . '/../../../config/exchange_rates.yaml';
        $this->config = new ExchangeRatesConfig($configPath);
    }

    public function testGetAllowedCurrencies(): void
    {
        $currencies = $this->config->getAllowedCurrencies();

        $this->assertCount(5, $currencies);

        $currencyCodes = array_map(fn($currency) => $currency->value, $currencies);
        $this->assertContains('EUR', $currencyCodes);
        $this->assertContains('USD', $currencyCodes);
        $this->assertContains('CZK', $currencyCodes);
        $this->assertContains('IDR', $currencyCodes);
        $this->assertContains('BRL', $currencyCodes);
    }

    public function testIsCurrencyAllowed(): void
    {
        $this->assertTrue($this->config->isCurrencyAllowed(new CurrencyCode('EUR')));
        $this->assertTrue($this->config->isCurrencyAllowed(new CurrencyCode('USD')));
        $this->assertFalse($this->config->isCurrencyAllowed(new CurrencyCode('GBP')));
        $this->assertFalse($this->config->isCurrencyAllowed(new CurrencyCode('JPY')));
    }

    public function testGetBuySpread(): void
    {
        $this->assertEquals(-0.15, $this->config->getBuySpread(new CurrencyCode('EUR')));
        $this->assertEquals(-0.15, $this->config->getBuySpread(new CurrencyCode('USD')));
        $this->assertNull($this->config->getBuySpread(new CurrencyCode('CZK')));
        $this->assertNull($this->config->getBuySpread(new CurrencyCode('GBP')));
    }

    public function testGetSellSpread(): void
    {
        $this->assertEquals(0.11, $this->config->getSellSpread(new CurrencyCode('EUR')));
        $this->assertEquals(0.11, $this->config->getSellSpread(new CurrencyCode('USD')));
        $this->assertEquals(0.2, $this->config->getSellSpread(new CurrencyCode('CZK')));
        $this->assertEquals(0.2, $this->config->getSellSpread(new CurrencyCode('IDR')));
        $this->assertEquals(0.2, $this->config->getSellSpread(new CurrencyCode('BRL')));
        $this->assertNull($this->config->getSellSpread(new CurrencyCode('GBP')));
    }
}

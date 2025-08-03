<?php

declare(strict_types=1);

namespace App\Tests\Service\Nbp;

use App\Service\Nbp\NbpCurrencyHistoryClient;
use App\Service\Nbp\NbpCurrencyHistoryProvider;
use App\ValueObject\CurrencyCode;
use PHPUnit\Framework\TestCase;

class NbpCurrencyHistoryProviderTest extends TestCase
{
    private NbpCurrencyHistoryProvider $provider;

    protected function setUp(): void
    {
        $client = new NbpCurrencyHistoryClient();
        $this->provider = new NbpCurrencyHistoryProvider($client);
    }

    public function testGetCurrencyHistoryLast14Days(): void
    {
        $currencyCode = new CurrencyCode('EUR');
        $result = $this->provider->getCurrencyHistoryLast14Days($currencyCode);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('table', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('rates', $result);

        $this->assertEquals('A', $result['table']);
        $this->assertInstanceOf(CurrencyCode::class, $result['code']);
        $this->assertEquals('EUR', $result['code']->value);
        $this->assertIsArray($result['rates']);
        $this->assertLessThanOrEqual(14, count($result['rates']));
    }

    public function testGetCurrencyHistoryLast14DaysAsJson(): void
    {
        $currencyCode = new CurrencyCode('USD');
        $json = $this->provider->getCurrencyHistoryLast14DaysAsJson($currencyCode);

        $this->assertIsString($json);
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('table', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('rates', $data);

        $this->assertEquals('USD', $data['code']);
        $this->assertIsArray($data['rates']);

        foreach ($data['rates'] as $rate) {
            $this->assertArrayHasKey('no', $rate);
            $this->assertArrayHasKey('effectiveDate', $rate);
            $this->assertArrayHasKey('mid', $rate);
            $this->assertIsNumeric($rate['mid']);
        }
    }
}

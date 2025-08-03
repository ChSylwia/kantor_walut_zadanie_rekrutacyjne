<?php

declare(strict_types=1);

namespace App\Service\ExchangeRates;

use App\ValueObject\CurrencyCode;
use Symfony\Component\Yaml\Yaml;

class ExchangeRatesConfig
{
    private array $config;

    public function __construct(string $configPath)
    {
        $this->config = Yaml::parseFile($configPath);
    }

    /**
     * @return CurrencyCode[]
     */
    public function getAllowedCurrencies(): array
    {
        $spreads = $this->config['exchange_rates']['spreads'] ?? [];

        return array_map(
            fn(string $currency) => new CurrencyCode($currency),
            array_keys($spreads)
        );
    }

    public function getBuySpread(CurrencyCode $currency): ?float
    {
        return $this->config['exchange_rates']['spreads'][$currency->value]['buy'] ?? null;
    }

    public function getSellSpread(CurrencyCode $currency): ?float
    {
        return $this->config['exchange_rates']['spreads'][$currency->value]['sell'] ?? null;
    }

    public function isCurrencyAllowed(CurrencyCode $currency): bool
    {
        return isset($this->config['exchange_rates']['spreads'][$currency->value]);
    }
}

<?php
namespace App\Service;

use Symfony\Component\Yaml\Yaml;

class CurrencyCountryService
{
    private array $currencyMap;

    public function __construct(string $pathToYaml)
    {
        $this->currencyMap = Yaml::parseFile($pathToYaml);
    }

    public function getCountryCodeForCurrency(string $currency): ?string
    {
        return $this->currencyMap[$currency] ?? null;
    }

    public function getAll(): array
    {
        return $this->currencyMap;
    }
}
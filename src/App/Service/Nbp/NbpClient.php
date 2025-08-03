<?php

declare(strict_types=1);

namespace App\Service\Nbp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

final readonly class NbpClient
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'NBP-Client/1.0'
            ]
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getLastExchangeRatesTables(int $lastCount = 2, string $table = 'A'): array
    {
        $url = "https://api.nbp.pl/api/exchangerates/tables/{$table}/last/{$lastCount}?format=json";

        $response = $this->httpClient->get($url);
        $content = $response->getBody()->getContents();

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
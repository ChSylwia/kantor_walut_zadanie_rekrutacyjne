<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExchangeRate\AirtableLastExchangesProvider;
use App\Service\ExchangeRate\ExchangeRateWithSpreadsProvider;
use App\Service\LastRatesUpdater\LastRatesUpdaterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CurrencyExchangeController extends AbstractController
{
    public function __construct(
        private readonly AirtableLastExchangesProvider $airtableLastExchangesProvider,
        private readonly ExchangeRateWithSpreadsProvider $exchangeRateWithSpreadsProvider,
        private readonly LastRatesUpdaterService $lastRatesUpdaterService
    ) {
    }

    public function getLastRates(): JsonResponse
    {
        $lastRatesDto = $this->airtableLastExchangesProvider->getLastRates();
        return new JsonResponse($lastRatesDto);
    }

    public function getExchangeRatesWithSpreads(): JsonResponse
    {
        try {
            $ratesWithSpreads = $this->exchangeRateWithSpreadsProvider->getExchangeRatesWithSpreads();

            return new JsonResponse($ratesWithSpreads);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to fetch exchange rates',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

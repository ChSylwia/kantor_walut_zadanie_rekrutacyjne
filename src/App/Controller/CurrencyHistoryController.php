<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Nbp\NbpCurrencyHistoryProvider;
use App\ValueObject\CurrencyCode;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyHistoryController extends AbstractController
{
    public function __construct(
        private readonly NbpCurrencyHistoryProvider $currencyHistoryProvider
    ) {
    }
    #[Route('/api/currency-history/{currencyCode}', name: 'currency_history', methods: ['GET'])]
    public function getCurrencyHistory(string $currencyCode, Request $request): JsonResponse
    {
        try {
            $currency = new CurrencyCode(strtoupper($currencyCode));

            $endDate = null;
            if ($request->query->has('endDate')) {
                $endDateString = $request->query->get('endDate');
                $endDate = new \DateTimeImmutable($endDateString);
            }

            $historyData = $this->currencyHistoryProvider->getCurrencyHistoryLast14Days($currency, 'A', $endDate);

            $response = [
                'table' => $historyData['table'],
                'currency' => $historyData['currency'],
                'code' => $historyData['code']->value,
                'rates' => array_map(function ($rate) {
                    return [
                        'no' => $rate['no'],
                        'effectiveDate' => $rate['effectiveDate']->date->format('Y-m-d'),
                        'mid' => $rate['mid']->value
                    ];
                }, $historyData['rates'])
            ];

            return new JsonResponse($response);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Invalid currency code: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return new JsonResponse(['error' => 'Currency data not found for the last 14 days'], Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse(['error' => 'API error: ' . $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

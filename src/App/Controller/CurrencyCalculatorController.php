<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CurrencyCalculator\CurrencyCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyCalculatorController extends AbstractController
{
    public function __construct(
        private readonly CurrencyCalculatorService $calculatorService
    ) {
    }

    #[Route('/api/calculate', name: 'currency_calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'error' => 'Invalid JSON payload'
                ], Response::HTTP_BAD_REQUEST);
            }

            $amount = $data['amount'] ?? null;
            $fromCurrency = $data['fromCurrency'] ?? null;
            $toCurrency = $data['toCurrency'] ?? null;
            $operationType = $data['operationType'] ?? 'mid';

            if (!$amount || !$fromCurrency || !$toCurrency) {
                return new JsonResponse([
                    'error' => 'Missing required parameters: amount, fromCurrency, toCurrency'
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->calculatorService->calculate(
                (float) $amount,
                $fromCurrency,
                $toCurrency,
                $operationType
            );

            return new JsonResponse($result);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php
namespace App\Controller;

use App\Service\CurrencyCountryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends AbstractController
{
    #[Route('/api/currencies/flags', name: 'currency_flags', methods: ['GET'])]
    public function getCurrencyFlags(CurrencyCountryService $service): JsonResponse
    {
        return $this->json($service->getAll());
    }
}
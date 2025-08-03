<?php

declare(strict_types=1);

namespace App\Service\Airtable;

final readonly class AirtableConfig
{
    public function __construct(
        public string $baseToken,
        public string $baseId,
        public string $apiUrl,
    ) {
    }

    public function getBaseUrl(): string
    {
        return $this->apiUrl . '/' . $this->baseId;
    }
}

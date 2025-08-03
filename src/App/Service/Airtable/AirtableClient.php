<?php

declare(strict_types=1);

namespace App\Service\Airtable;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use JsonException;

final readonly class AirtableClient
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 500; // 0.5 seconds
    private const BULK_UPLOAD_CHUNK_SIZE = 10; // Airtable free account limit

    private Client $httpClient;

    public function __construct(
        private AirtableConfig $config
    ) {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->baseToken,
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Helper method for JSON decoding with proper error handling
     */
    private function decodeJson(string $json): array
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON response: ' . $e->getMessage());
        }
    }

    /**
     * Execute HTTP request with retry logic for 429 errors
     */
    private function executeWithRetry(callable $httpCall): mixed
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                return $httpCall();
            } catch (ClientException $e) {
                $attempt++;

                // Check if it's a 429 Too Many Requests error
                if ($e->getResponse()?->getStatusCode() === 429 && $attempt < self::MAX_RETRIES) {
                    // Wait before retrying (convert milliseconds to microseconds)
                    usleep(self::RETRY_DELAY_MS * 1000);
                    continue;
                }

                // Re-throw if it's not a 429 error or we've exhausted retries
                throw new InvalidArgumentException(
                    'HTTP request failed: ' . $e->getMessage() .
                    ($attempt >= self::MAX_RETRIES ? ' (max retries exceeded)' : '')
                );
            } catch (GuzzleException $e) {
                throw new InvalidArgumentException('HTTP request failed: ' . $e->getMessage());
            }
        }

        throw new InvalidArgumentException('Max retries exceeded for HTTP request');
    }    /**
         * Fetch all records from a specific table in chunks of 100
         */
    public function getAllRecords(string $tableName, array $parameters = []): array
    {
        $allRecords = [];
        $offset = null;
        $pageSize = 100;

        do {
            $params = array_merge($parameters, ['pageSize' => $pageSize]);
            if ($offset) {
                $params['offset'] = $offset;
            }

            $data = $this->executeWithRetry(function () use ($tableName, $params) {
                $url = $this->config->getBaseUrl() . '/' . urlencode($tableName);
                $response = $this->httpClient->get($url, ['query' => $params]);
                return $this->decodeJson($response->getBody()->getContents());
            });

            $records = $data['records'] ?? [];
            $allRecords = array_merge($allRecords, $records);
            $offset = $data['offset'] ?? null;

            // If we got fewer records than page size, this is the last page
            if (count($records) < $pageSize) {
                break;
            }

        } while ($offset);

        return $allRecords;
    }

    /**
     * Create multiple records in a specific table (bulk upload in chunks)
     */
    public function createBulkRecords(string $tableName, array $recordsData): array
    {
        $chunks = array_chunk($recordsData, self::BULK_UPLOAD_CHUNK_SIZE);
        $allResults = [];

        foreach ($chunks as $chunk) {
            $url = $this->config->getBaseUrl() . '/' . urlencode($tableName);

            // Format records for Airtable API
            $records = array_map(fn($fields) => ['fields' => $fields], $chunk);

            $result = $this->executeWithRetry(function () use ($url, $records) {
                $response = $this->httpClient->post($url, [
                    'json' => ['records' => $records]
                ]);

                return $this->decodeJson($response->getBody()->getContents());
            });

            $allResults = array_merge($allResults, $result['records'] ?? []);

            // Small delay between chunks to avoid rate limiting
            if (count($chunks) > 1) {
                usleep(200000); // 0.2 seconds between chunks
            }
        }

        return $allResults;
    }    /**
         * Delete all records from a table
         */
    private function truncateTable(string $tableName): void
    {
        $allRecords = [];
        $offset = null;
        $pageSize = 100;

        // Get all records in batches
        do {
            $params = ['pageSize' => $pageSize];
            if ($offset) {
                $params['offset'] = $offset;
            }

            $data = $this->executeWithRetry(function () use ($tableName, $params) {
                $url = $this->config->getBaseUrl() . '/' . urlencode($tableName);
                $response = $this->httpClient->get($url, ['query' => $params]);
                return $this->decodeJson($response->getBody()->getContents());
            });

            $records = $data['records'] ?? [];
            $allRecords = array_merge($allRecords, array_column($records, 'id'));
            $offset = $data['offset'] ?? null;

            // If we got fewer records than page size, this is the last page
            if (count($records) < $pageSize) {
                break;
            }

        } while ($offset);

        // Delete records in chunks of 10
        if (!empty($allRecords)) {
            $deleteChunks = array_chunk($allRecords, self::BULK_UPLOAD_CHUNK_SIZE);

            foreach ($deleteChunks as $chunk) {
                $this->executeWithRetry(function () use ($tableName, $chunk) {
                    $url = $this->config->getBaseUrl() . '/' . urlencode($tableName);
                    $queryParams = array_map(fn($id) => "records[]=$id", $chunk);
                    $deleteUrl = $url . '?' . implode('&', $queryParams);

                    $this->httpClient->delete($deleteUrl);
                    return true;
                });

                // Small delay between delete chunks
                if (count($deleteChunks) > 1) {
                    usleep(200000); // 0.2 seconds
                }
            }
        }
    }

    /**
     * Upsert table data: truncate table and upload new data in bulk
     * This method will delete all existing records and upload new ones
     */
    public function upsertTable(string $tableName, array $recordsData): array
    {
        if (empty($recordsData)) {
            throw new InvalidArgumentException('Records data cannot be empty');
        }

        // Step 1: Truncate (delete all existing records)
        $this->truncateTable($tableName);

        // Step 2: Upload new data in bulk
        return $this->createBulkRecords($tableName, $recordsData);
    }
}

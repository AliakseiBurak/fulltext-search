<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ManticoreService
{
    private Client $client;
    private string $host;
    private int $port;

    public function __construct(string $host = 'manticore', int $port = 9308)
    {
        $this->host = $host;
        $this->port = $port;
        $this->client = new Client([
            'base_uri' => "http://{$host}:{$port}/",
            'timeout' => 30
        ]);
    }

    /**
     * Create an index with versioning support
     */
    public function createIndex(string $indexName, array $fields = []): bool
    {
        try {
            // Check if index already exists
            $existingIndices = $this->getIndices();
            if (in_array($indexName, $existingIndices)) {
                return false;
            }

            // Build fields definition for RT index
            $fieldsDefinition = '';
            foreach ($fields as $field => $type) {
                if ($type === 'text') {
                    $fieldsDefinition .= "rt_field = {$field}\n    ";
                } elseif ($type === 'string') {
                    $fieldsDefinition .= "rt_attr_string = {$field}\n    ";
                } elseif ($type === 'int') {
                    $fieldsDefinition .= "rt_attr_uint = {$field}\n    ";
                } elseif ($type === 'timestamp') {
                    $fieldsDefinition .= "rt_attr_timestamp = {$field}\n    ";
                }
            }

            // Create real-time index
            $sql = "CREATE TABLE {$indexName} (
                id BIGINT PRIMARY KEY,
                {$fieldsDefinition}
                title TEXT,
                content TEXT,
                date_added TIMESTAMP
            )";

            $response = $this->client->request('POST', 'sql', [
                'form_params' => ['query' => $sql]
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            error_log("Error creating index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an index
     */
    public function deleteIndex(string $indexName): bool
    {
        try {
            $sql = "DROP TABLE {$indexName}";
            $response = $this->client->request('POST', 'sql', [
                'form_params' => ['query' => $sql]
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            error_log("Error deleting index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of all indices
     */
    public function getIndices(): array
    {
        try {
            $sql = "SHOW TABLES";
            $response = $this->client->request('POST', 'sql', [
                'form_params' => ['query' => $sql]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $indices = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $row) {
                    if (isset($row['Table'])) {
                        $indices[] = $row['Table'];
                    }
                }
            }

            return $indices;
        } catch (RequestException $e) {
            error_log("Error getting indices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add document to index
     */
    public function addDocument(string $indexName, array $document): bool
    {
        try {
            // Prepare INSERT statement
            $columns = implode(', ', array_keys($document));
            $values = [];
            
            foreach ($document as $value) {
                if (is_string($value)) {
                    $values[] = "'" . addslashes($value) . "'";
                } else {
                    $values[] = $value;
                }
            }
            
            $valuesStr = implode(', ', $values);
            $sql = "INSERT INTO {$indexName} ({$columns}) VALUES ({$valuesStr})";

            $response = $this->client->request('POST', 'sql', [
                'form_params' => ['query' => $sql]
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            error_log("Error adding document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search in index with filters
     */
    public function search(string $indexName, string $query, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        try {
            // Build WHERE clause from filters
            $whereClauses = ["MATCH('{$query}')"];
            
            foreach ($filters as $field => $value) {
                if (is_string($value)) {
                    $whereClauses[] = "{$field} = '" . addslashes($value) . "'";
                } else {
                    $whereClauses[] = "{$field} = {$value}";
                }
            }
            
            $whereClause = implode(' AND ', $whereClauses);
            
            $sql = "SELECT *, WEIGHT() as relevance FROM {$indexName} 
                    WHERE {$whereClause} 
                    ORDER BY relevance DESC 
                    LIMIT {$offset}, {$limit}";

            $response = $this->client->request('POST', 'sql', [
                'form_params' => ['query' => $sql]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }

            return [];
        } catch (RequestException $e) {
            error_log("Error searching: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Switch active index for search (in a multi-version setup)
     */
    public function switchActiveIndex(string $newIndexName): bool
    {
        // In a real implementation, this would update some configuration
        // to point to the new index version
        // For now, we'll just verify the index exists
        $indices = $this->getIndices();
        return in_array($newIndexName, $indices);
    }
}
<?php

namespace App\Controller;

use App\Service\ManticoreService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    private ManticoreService $manticoreService;

    public function __construct(ManticoreService $manticoreService)
    {
        $this->manticoreService = $manticoreService;
    }

    /**
     * Create a new index
     */
    public function createIndex(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        
        $indexName = $params['index'] ?? '';
        $fields = $params['fields'] ?? [
            'title' => 'text',
            'content' => 'text',
            'date_added' => 'timestamp'
        ];

        if (empty($indexName)) {
            return new JsonResponse(['error' => 'Index name is required'], Response::HTTP_BAD_REQUEST);
        }

        // Validate index name format (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $indexName)) {
            return new JsonResponse(['error' => 'Invalid index name format'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->manticoreService->createIndex($indexName, $fields);

        if ($result) {
            return new JsonResponse([
                'message' => "Index '{$indexName}' created successfully",
                'index' => $indexName
            ]);
        } else {
            return new JsonResponse(['error' => 'Failed to create index'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete an index
     */
    public function deleteIndex(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        $indexName = $params['index'] ?? '';

        if (empty($indexName)) {
            return new JsonResponse(['error' => 'Index name is required'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->manticoreService->deleteIndex($indexName);

        if ($result) {
            return new JsonResponse([
                'message' => "Index '{$indexName}' deleted successfully"
            ]);
        } else {
            return new JsonResponse(['error' => 'Failed to delete index'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all indices
     */
    public function getIndices(): JsonResponse
    {
        $indices = $this->manticoreService->getIndices();
        
        return new JsonResponse([
            'indices' => $indices
        ]);
    }
}
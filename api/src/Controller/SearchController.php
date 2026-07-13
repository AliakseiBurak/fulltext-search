<?php

namespace App\Controller;

use App\Service\ManticoreService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    private ManticoreService $manticoreService;

    public function __construct(ManticoreService $manticoreService)
    {
        $this->manticoreService = $manticoreService;
    }

    /**
     * Search endpoint
     */
    public function search(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        
        $index = $params['index'] ?? 'documents';
        $query = $params['query'] ?? '';
        $filters = $params['filters'] ?? [];
        $limit = $params['limit'] ?? 20;
        $offset = $params['offset'] ?? 0;

        if (empty($query)) {
            return new JsonResponse(['error' => 'Query parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $results = $this->manticoreService->search($index, $query, $filters, $limit, $offset);

        return new JsonResponse([
            'query' => $query,
            'results' => $results,
            'total' => count($results)
        ]);
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

    /**
     * Switch active index
     */
    public function switchIndex(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        $indexName = $params['index'] ?? '';

        if (empty($indexName)) {
            return new JsonResponse(['error' => 'Index name is required'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->manticoreService->switchActiveIndex($indexName);

        if ($result) {
            return new JsonResponse([
                'message' => "Successfully switched to index: {$indexName}",
                'active_index' => $indexName
            ]);
        } else {
            return new JsonResponse(['error' => 'Failed to switch index'], Response::HTTP_BAD_REQUEST);
        }
    }
}
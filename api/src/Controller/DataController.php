<?php

namespace App\Controller;

use App\Service\ManticoreService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DataController
{
    private ManticoreService $manticoreService;

    public function __construct(ManticoreService $manticoreService)
    {
        $this->manticoreService = $manticoreService;
    }

    /**
     * Add a document to an index
     */
    public function addDocument(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        
        $indexName = $params['index'] ?? 'documents';
        $document = $params['document'] ?? [];

        if (empty($document)) {
            return new JsonResponse(['error' => 'Document data is required'], Response::HTTP_BAD_REQUEST);
        }

        // Ensure document has an ID
        if (!isset($document['id'])) {
            return new JsonResponse(['error' => 'Document must have an ID'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->manticoreService->addDocument($indexName, $document);

        if ($result) {
            return new JsonResponse([
                'message' => "Document added successfully to index '{$indexName}'",
                'document_id' => $document['id']
            ]);
        } else {
            return new JsonResponse(['error' => 'Failed to add document'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add multiple documents to an index
     */
    public function addDocuments(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        
        $indexName = $params['index'] ?? 'documents';
        $documents = $params['documents'] ?? [];

        if (empty($documents)) {
            return new JsonResponse(['error' => 'Documents data is required'], Response::HTTP_BAD_REQUEST);
        }

        $addedCount = 0;
        $errors = [];

        foreach ($documents as $idx => $document) {
            if (!isset($document['id'])) {
                $errors[] = "Document at index {$idx} must have an ID";
                continue;
            }

            $result = $this->manticoreService->addDocument($indexName, $document);
            
            if ($result) {
                $addedCount++;
            } else {
                $errors[] = "Failed to add document with ID: {$document['id']}";
            }
        }

        $response = [
            'message' => "Processed {$addedCount} out of " . count($documents) . " documents",
            'added_count' => $addedCount,
            'total_count' => count($documents)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
            return new JsonResponse($response, Response::HTTP_PARTIAL_CONTENT);
        }

        return new JsonResponse($response);
    }
}
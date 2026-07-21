<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

trait ApiResponse
{
    /**
     * Send a success response.
     *
     * @param  mixed  $data
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Send an error response.
     *
     * @param  mixed  $errors
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Standardize Single Resource responses.
     */
    protected function resourceResponse(JsonResource $resource, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource,
        ], $code);
    }

    /**
     * Standardize Paginated Resource responses.
     */
    protected function paginatedResponse(AnonymousResourceCollection $resource, string $message = 'Success', int $code = 200): JsonResponse
    {
        $paginated = $resource->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginated['data'],
            'links' => $paginated['links'] ?? null,
            'meta' => $paginated['meta'] ?? null,
        ], $code);
    }

    /**
     * Standardize Bulk Resource responses.
     */
    protected function bulkResponse(array $result, string $action, string $resourceClass, string $modelName, array $relations = []): JsonResponse
    {
        $affected = $result['affected'];
        $failedIds = $result['failed_ids'] ?? [];

        // Eager load relations if specified
        if (! empty($relations) && $affected->isNotEmpty()) {
            $affected->load($relations);
        }

        $pluralName = Str::plural($modelName);

        $message = sprintf(
            'Bulk %s operation completed. %d %s %s, %d failed.',
            $action,
            $affected->count(),
            $pluralName,
            $action,
            count($failedIds)
        );

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'affected' => $resourceClass::collection($affected),
                'failed_ids' => $failedIds,
            ],
            'errors' => null,
        ]);
    }
}

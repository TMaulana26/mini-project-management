<?php

namespace App\Traits;

use App\Http\Requests\Shared\BulkRequest;
use Illuminate\Http\JsonResponse;

trait HandlesBulkAndSoftDeletes
{
    /**
     * Define the service to be used by the trait methods.
     * Ensure the controller using this trait has the appropriate service injected.
     */
    abstract protected function getService();

    /**
     * Define the resource class to be used for responses.
     */
    abstract protected function getResourceClass(): string;

    /**
     * Define the singular name of the model for response messages.
     */
    abstract protected function getModelName(): string;

    /**
     * Define relations to eager load for bulk responses.
     */
    protected function getEagerLoadRelations(): array
    {
        return [];
    }

    /**
     * Restore the specified soft-deleted resource.
     */
    public function restore(string $id): JsonResponse
    {
        $model = $this->getService()->restore($id);
        $resourceClass = $this->getResourceClass();

        return $this->resourceResponse(
            new $resourceClass($model),
            ucfirst($this->getModelName()).' restored successfully.'
        );
    }

    /**
     * Permanently remove the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $model = $this->getService()->forceDelete($id);
        $resourceClass = $this->getResourceClass();

        return $this->resourceResponse(
            new $resourceClass($model),
            ucfirst($this->getModelName()).' permanently deleted.'
        );
    }

    /**
     * Remove multiple resources (Soft Delete).
     */
    public function bulkDestroy(BulkRequest $request): JsonResponse
    {
        $result = $this->getService()->handleBulkOperation($request->validated()['ids'], 'delete');

        return $this->bulkResponse(
            $result,
            'deleted',
            $this->getResourceClass(),
            $this->getModelName(),
            $this->getEagerLoadRelations()
        );
    }

    /**
     * Toggle active status for multiple resources.
     */
    public function bulkToggleStatus(BulkRequest $request): JsonResponse
    {
        $result = $this->getService()->handleBulkOperation($request->validated()['ids'], 'toggle');

        return $this->bulkResponse(
            $result,
            'status toggled',
            $this->getResourceClass(),
            $this->getModelName(),
            $this->getEagerLoadRelations()
        );
    }

    /**
     * Restore multiple soft-deleted resources.
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        $result = $this->getService()->handleBulkOperation($request->validated()['ids'], 'restore');

        return $this->bulkResponse(
            $result,
            'restored',
            $this->getResourceClass(),
            $this->getModelName(),
            $this->getEagerLoadRelations()
        );
    }

    /**
     * Permanently remove multiple resources.
     */
    public function bulkForceDelete(BulkRequest $request): JsonResponse
    {
        $result = $this->getService()->handleBulkOperation($request->validated()['ids'], 'forceDelete');

        return $this->bulkResponse(
            $result,
            'permanently deleted',
            $this->getResourceClass(),
            $this->getModelName(),
            $this->getEagerLoadRelations()
        );
    }
}

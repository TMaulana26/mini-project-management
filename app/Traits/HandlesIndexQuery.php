<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait HandlesIndexQuery
{
    /**
     * Handle common index query logic: filtering, searching, sorting, and pagination.
     */
    protected function handleIndexQuery(
        Builder $query,
        array $params,
        array $searchColumns = [],
        ?callable $modifyQuery = null,
        int $defaultPerPage = 10
    ): LengthAwarePaginator {
        // Handle Soft Deletes
        $query->when(($params['trashed'] ?? null) === 'only', fn ($q) => $q->onlyTrashed())
            ->when(($params['trashed'] ?? null) === 'with', fn ($q) => $q->withTrashed());

        // Handle Status filtering
        if (isset($params['status'])) {
            $query->where('is_active', $params['status'] === 'active');
        }

        // Handle Search
        if (! empty($params['search']) && ! empty($searchColumns)) {
            $query->where(function (Builder $q) use ($params, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', "%{$params['search']}%");
                }
            });
        }

        // Custom Query Modification
        if ($modifyQuery) {
            $modifyQuery($query);
        }

        // Handle Sorting
        $sortBy = $params['sort_by'] ?? 'id';
        $sortOrder = $params['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Handle Pagination
        $perPage = $params['per_page'] ?? $defaultPerPage;
        if ((int) $perPage === -1) {
            $perPage = $query->count() ?: 1;
        }

        return $query->paginate((int) $perPage)->withQueryString();
    }
}

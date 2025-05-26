<?php

namespace DryRun\Brands\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use DryRun\Brands\Models\Brand;

interface BrandServiceInterface
{
    /**
     * Retrieve all brands with optional filters and pagination.
     *
     * @param array $filters (e.g., ['search' => 'term', 'status' => 'active', 'trashed' => 'only/with/without'])
     * @param int $perPage
     * @param array $relations Eager load relations
     * @param string $sortBy
     * @param string $sortDirection
     * @return LengthAwarePaginator
     */
    public function getAll(array $filters = [], int $perPage = 15, array $relations = [], string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator;

    /**
     * Retrieve data formatted for AJAX DataTables.
     *
     * @param array $dataTableParams (draw, start, length, search, order, columns, trashed_filter)
     * @param array $relations Eager load relations
     * @return array ['data' => array, 'total' => int, 'filtered' => int]
     */
    public function getDataForDataTable(array $dataTableParams, array $relations = []): array;

    /**
     * Retrieve all brands paginated (primarily for APIs).
     *
     * @param array $filters (search, specific field filters, sort_by, sort_direction, trashed)
     * @param int $perPage
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Find a brand by its ID, optionally including trashed items.
     *
     * @param int|string $id
     * @param array $relations Eager load relations
     * @param bool $withTrashed Include soft-deleted models
     * @return Brand|null
     */
    public function getById($id, array $relations = [], bool $withTrashed = false): ?Brand;

    /**
     * Create a new brand.
     *
     * @param array $data
     * @return Brand
     */
    public function create(array $data): Brand;

    /**
     * Update an existing brand.
     *
     * @param int|string $id
     * @param array $data
     * @return Brand|null
     */
    public function update($id, array $data): ?Brand;

    /**
     * Soft delete a brand.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Restore a soft-deleted brand.
     *
     * @param int|string $id
     * @return bool
     */
    public function restore($id): bool;

    /**
     * Permanently delete a brand.
     *
     * @param int|string $id
     * @return bool
     */
    public function forceDelete($id): bool;
}

<?php

namespace DryRun\Brands\Services;

use DryRun\Brands\Contracts\BrandServiceInterface;
use DryRun\Brands\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// TODO: Uncomment if using model events dispatched from service
// use DryRun\Brands\Events\BrandCreated;
// use DryRun\Brands\Events\BrandUpdated;
// use DryRun\Brands\Events\BrandDeleted;

class BrandService implements BrandServiceInterface
{
    protected array $searchableColumns = [ /* Populated by constructor or define manually */ ];
    protected array $sortableColumns = [ 'id' => 'id', 'created_at' => 'created_at', 'updated_at' => 'updated_at' ];

    public function __construct()
    {
        if (empty($this->searchableColumns) && Schema::hasTable('brands')) {
            $allColumns = Schema::getColumnListing('brands');
            $excluded = ['id', 'password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at'];
            foreach($allColumns as $col) {
                if (in_array($col, $excluded)) continue;
                try {
                    $type = Schema::getColumnType('brands', $col);
                    if (in_array($type, ['string', 'text', 'varchar', 'char'])) {
                        $this->searchableColumns[] = $col;
                    }
                    if(!array_key_exists(Str::snake($col), $this->sortableColumns)) {
                         $this->sortableColumns[Str::snake($col)] = $col;
                    }
                } catch (\Exception $e) { /* ignore */ }
            }
        }
        if (empty($this->searchableColumns)) $this->searchableColumns = ['id'];
    }

    public function getAll(array $filters = [], int $perPage = 15, array $relations = [], string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = \DryRun\Brands\Models\Brand::query();
        if (!empty($relations)) $query->with($relations);

        // Handle trashed filter
        if (isset($filters['trashed'])) {
            if ($filters['trashed'] === 'only') $query->onlyTrashed();
            if ($filters['trashed'] === 'with') $query->withTrashed();
        }

        if (!empty($filters['search']) && !empty($this->searchableColumns)) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                foreach ($this->searchableColumns as $column) $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
            });
        }

        $sortColumn = $this->sortableColumns[$sortBy] ?? $this->sortableColumns[array_key_first($this->sortableColumns)] ?? 'id';
        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortColumn, $direction);

        return $query->paginate($perPage);
    }

    public function getDataForDataTable(array $dataTableParams, array $relations = []): array
    {
        $query = \DryRun\Brands\Models\Brand::query();

        // Handle trashed filter from DataTables (e.g., if you add a custom filter input)
        if (isset($dataTableParams['trashed_filter'])) {
            if ($dataTableParams['trashed_filter'] === 'only') $query->onlyTrashed();
            elseif ($dataTableParams['trashed_filter'] === 'with') $query->withTrashed();
            // Default is 'without' (no trashed items)
        }

        $totalRecords = $query->count();

        if (!empty($relations)) $query->with($relations);

        $globalSearchTerm = $dataTableParams['search'] ?? '';
        if (!empty($globalSearchTerm) && !empty($this->searchableColumns)) {
            $query->where(function ($q) use ($globalSearchTerm) {
                foreach ($this->searchableColumns as $column) $q->orWhere($column, 'LIKE', "%{$globalSearchTerm}%");
            });
        }

        $recordsFiltered = $query->count();

        if (isset($dataTableParams['order']) && count($dataTableParams['order'])) {
            foreach ($dataTableParams['order'] as $order) {
                $columnIndex = intval($order['column']);
                $columnData = $dataTableParams['columns'][$columnIndex] ?? null;
                $columnName = $columnData['data'] ?? null;
                $sortDir = strtolower($order['dir']) === 'asc' ? 'asc' : 'desc';

                $dbColumn = $this->sortableColumns[$columnName] ?? null;
                if (!$dbColumn && Schema::hasTable('brands') && in_array($columnName, Schema::getColumnListing('brands'))) {
                    $dbColumn = $columnName;
                }
                if ($dbColumn) $query->orderBy($dbColumn, $sortDir);
            }
        } else {
            $query->latest($this->sortableColumns['created_at'] ?? 'created_at');
        }

        $start = $dataTableParams['start'] ?? 0;
        $length = $dataTableParams['length'] ?? 10;
        $data = ($length == -1) ? $query->get() : $query->skip($start)->take($length)->get();

        $transformedData = $data->map(function($item) {
            $arrayItem = $item->toArray();
            if (isset($arrayItem['created_at']) && $item->created_at instanceof \Carbon\Carbon) {
                $arrayItem['created_at'] = $item->created_at->toIso8601String();
            }
            if (isset($arrayItem['updated_at']) && $item->updated_at instanceof \Carbon\Carbon) {
                $arrayItem['updated_at'] = $item->updated_at->toIso8601String();
            }
            if (isset($arrayItem['deleted_at']) && $item->deleted_at instanceof \Carbon\Carbon) { // For trashed view
                $arrayItem['deleted_at'] = $item->deleted_at->toIso8601String();
            }
            // Add a placeholder for actions, actual HTML rendering is done in Blade/JS
            $arrayItem['actions'] = ''; // This will be populated by DataTables render function
            return $arrayItem;
        })->toArray();

        return [ 'data' => $transformedData, 'total' => $totalRecords, 'filtered' => $recordsFiltered ];
    }

    public function getAllPaginated(array $filters, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->getAll($filters, $perPage, $relations, $filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc');
    }

    public function getById($id, array $relations = [], bool $withTrashed = false): ?Brand
    {
        try {
            $query = \DryRun\Brands\Models\Brand::query();
            if ($withTrashed) $query->withTrashed();
            if (!empty($relations)) $query->with($relations);
            return $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Brand not found: {$id}", ['module' => 'Brands']);
            throw $e;
        }
    }

    public function create(array $data): Brand
    {
        DB::beginTransaction();
        try {
            // Handle BelongsToMany relationships (e.g., tags)
            $manyToManyData = [];
            // Example: if (isset($data['tags_ids'])) { $manyToManyData['tags'] = $data['tags_ids']; unset($data['tags_ids']); }
            // TODO: Generalize this for any BelongsToMany relationship detected by the command

            $brand = \DryRun\Brands\Models\Brand::create($data);

            // Sync BelongsToMany relationships
            // Example: if (!empty($manyToManyData['tags'])) { $brand->tags()->sync($manyToManyData['tags']); }
            // TODO: Generalize sync logic

            DB::commit();
            Log::info("Brand created: {$brand->id}}", ['module' => 'Brands']);
            return $brand;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to create Brand: " . $e->getMessage(), ['data' => $data, 'exception' => $e, 'module' => 'Brands']);
            throw $e;
        }
    }

    public function update($id, array $data): ?Brand
    {
        $brand = $this->getById($id);
        DB::beginTransaction();
        try {
            // Handle BelongsToMany relationships
            $manyToManyData = [];
            // Example: if (array_key_exists('tags_ids', $data)) { $manyToManyData['tags'] = $data['tags_ids'] ?? []; unset($data['tags_ids']); }
            // TODO: Generalize this

            $brand->update($data);

            // Sync BelongsToMany relationships
            // Example: if (array_key_exists('tags', $manyToManyData)) { $brand->tags()->sync($manyToManyData['tags']); }
            // TODO: Generalize sync logic

            DB::commit();
            Log::info("Brand updated: {$brand->id}}", ['module' => 'Brands']);
            return $brand->fresh($brand->getRelations()); // Eager load relations again if needed
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to update Brand {$id}: " . $e->getMessage(), ['data' => $data, 'exception' => $e, 'module' => 'Brands']);
            throw $e;
        }
    }

    public function delete($id): bool
    {
        $brand = $this->getById($id);
        DB::beginTransaction();
        try {
            $result = $brand->delete(); // Soft delete
            DB::commit();
            Log::info("Brand soft deleted: {$id}", ['module' => 'Brands']);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to soft delete Brand {$id}: " . $e->getMessage(), ['exception' => $e, 'module' => 'Brands']);
            throw $e;
        }
    }

    public function restore($id): bool
    {
        $brand = $this->getById($id, [], true); // Get even if trashed
        if (!method_exists($brand, 'restore')) {
             Log::error("Model Brand does not use SoftDeletes or restore method missing.", ['id' => $id]);
             throw new Exception("Cannot restore this model.");
        }
        DB::beginTransaction();
        try {
            $result = $brand->restore();
            DB::commit();
            Log::info("Brand restored: {$id}", ['module' => 'Brands']);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to restore Brand {$id}: " . $e->getMessage(), ['exception' => $e, 'module' => 'Brands']);
            throw $e;
        }
    }

    public function forceDelete($id): bool
    {
        $brand = $this->getById($id, [], true); // Get even if trashed
         if (!method_exists($brand, 'forceDelete')) {
             Log::error("Model Brand does not use SoftDeletes or forceDelete method missing.", ['id' => $id]);
             throw new Exception("Cannot force delete this model.");
        }
        DB::beginTransaction();
        try {
            // TODO: Handle detachment for BelongsToMany relationships before force delete if cascade is not set up
            // Example: $brand->tags()->detach();
            $result = $brand->forceDelete();
            DB::commit();
            Log::info("Brand force deleted: {$id}", ['module' => 'Brands']);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to force delete Brand {$id}: " . $e->getMessage(), ['exception' => $e, 'module' => 'Brands']);
            throw $e;
        }
    }
}

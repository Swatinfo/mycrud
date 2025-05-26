<?php

namespace DryRun\Brands\Http\Controllers\Web;

use App\Http\Controllers\Controller; // Assuming global base controller
use DryRun\Brands\Models\Brand;
use DryRun\Brands\Http\Requests\BrandRequest;
use DryRun\Brands\Contracts\BrandServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

// TODO: Import other models if needed for dropdowns in create/edit views, e.g., use App\Models\User;

class BrandController extends Controller
{
    protected $viewPathPrefix;
    protected $routeNamePrefix;
    protected BrandServiceInterface $brandService;

    public function __construct(\DryRun\Brands\Contracts\BrandServiceInterface $brandService)
    {
        $this->brandService = $brandService;
        // $this->middleware('auth'); // Apply to all methods or specific ones

        // Example of authorizing all resource methods using a policy
        // Ensure YourModelNamePolicy is created and registered
        // $this->authorizeResource(\DryRun\Brands\Models\Brand::class, 'brand');
        // Note: For authorizeResource to work correctly, your route parameter name
        // in routes/web.php for the resource should match the singular model name placeholder.
        // e.g., Route::resource('posts', PostController::class); -> parameter 'post'
        // If your parameter is different, e.g. 'article' for 'Post' model, authorize manually in each method.

        $this->viewPathPrefix = 'brands::brands';
        $this->routeNamePrefix = 'brands.brands';
    }

    /**
     * Display a listing of the resource.
     * Handles both regular view and AJAX DataTables requests.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Gate::authorize('viewAny', \DryRun\Brands\Models\Brand::class);

        if ($request->ajax()) {
            try {
                $dataTableParams = [
                    'draw' => $request->input('draw'),
                    'start' => $request->input('start', 0),
                    'length' => $request->input('length', 10),
                    'search' => $request->input('search.value', ''), // Global search
                    'order' => $request->input('order', [['column' => 0, 'dir' => 'desc']]), // Default order by ID desc
                    'columns' => $request->input('columns', []),
                    'trashed_filter' => $request->input('trashed_filter', 'without'), // For potential trashed filter in DataTables
                ];

                $results = $this->brandService->getDataForDataTable($dataTableParams); ; // Service method handles data fetching

                return response()->json([
                    'draw' => intval($dataTableParams['draw']),
                    'recordsTotal' => $results['total'] ?? 0,
                    'recordsFiltered' => $results['filtered'] ?? 0,
                    'data' => $results['data'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error("Error fetching brands for DataTables: " . $e->getMessage(), ['exception' => $e]);
                // Return a JSON error response that DataTables can understand
                return response()->json([
                    'error' => 'Could not retrieve data. Please try again later.',
                    'message' => $e->getMessage() // For debugging, consider removing in production
                ], 500);
            }
        }

        // For non-AJAX requests, just load the view. DataTables will fetch data via AJAX.
        return view($this->viewPathPrefix . '.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        // Gate::authorize('create', \DryRun\Brands\Models\Brand::class);

        // TODO: Fetch related data for dropdowns/selects if necessary, e.g., from the service
        // $relatedData = $this->brandService->getFormDataForCreate();
        // return view($this->viewPathPrefix . '.create', $relatedData);
        return view($this->viewPathPrefix . '.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \DryRun\Brands\Http\Requests\BrandRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BrandRequest $request)
    {
        // Authorization is typically handled by the FormRequest's authorize() method.
        // Or, Gate::authorize('create', \DryRun\Brands\Models\Brand::class);
        try {
            $brand = $this->brandService->create($request->validated());
            // TODO: Handle file uploads if any, usually done in the service or before calling service
            // TODO: Handle syncing of BelongsToMany relationships if any, usually done in the service

            return redirect()->route($this->routeNamePrefix . '.index')
                             ->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            Log::error("Error creating Brand: " . $e->getMessage(), [
                'request_data' => $request->validated(), // Be cautious logging sensitive data
                'exception' => $e
            ]);
            return back()->withInput()->with('error', 'Failed to create Brand. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(\DryRun\Brands\Models\Brand $brand) // Route model binding
    {
        // Gate::authorize('view', $brand);
        try {
            // If using a service to fetch with more relations or logic:
            // $brand = $this->brandService->getByIdWithDetails($brand->id);
            // Ensure the service method throws ModelNotFoundException if not found.

            // Eager load relationships if not already loaded and needed for the view
            // $brand->loadMissing(['user', 'comments']); // Example

            return view($this->viewPathPrefix . '.show', compact('brand'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Brand not found for show: id {$brand->id}}", ['exception' => $e]);
            return redirect()->route($this->routeNamePrefix . '.index')->with('error', 'Brand not found.');
        } catch (\Exception $e) {
            Log::error("Error showing Brand: id {$brand->id}} " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Could not display Brand. Please try again later.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(\DryRun\Brands\Models\Brand $brand)
    {
        // Gate::authorize('update', $brand);

        // TODO: Fetch related data for dropdowns/selects if necessary
        // $relatedData = $this->brandService->getFormDataForEdit($brand);
        // return view($this->viewPathPrefix . '.edit', array_merge(compact('brand'), $relatedData));
        return view($this->viewPathPrefix . '.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \DryRun\Brands\Http\Requests\BrandRequest  $request
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BrandRequest $request, \DryRun\Brands\Models\Brand $brand)
    {
        // Authorization is typically handled by the FormRequest's authorize() method.
        // Or, Gate::authorize('update', $brand);
        try {
            $brand = $this->brandService->update($brand->id, $request->validated());
            // TODO: Handle file uploads if any, usually done in the service or before calling service
            // TODO: Handle syncing of BelongsToMany relationships if any, usually done in the service

            return redirect()->route($this->routeNamePrefix . '.index')
                             ->with('success', 'Brand updated successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Brand not found for update: id {$brand->id}}", ['request_data' => $request->validated(), 'exception' => $e]);
            return redirect()->route($this->routeNamePrefix . '.index')->with('error', 'Brand not found.');
        } catch (\Exception $e) {
            Log::error("Error updating Brand: id {$brand->id}} " . $e->getMessage(), [
                'request_data' => $request->validated(), // Be cautious logging sensitive data
                'exception' => $e
            ]);
            return back()->withInput()->with('error', 'Failed to update Brand. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(\DryRun\Brands\Models\Brand $brand)
    {
        // Gate::authorize('delete', $brand);
        try {
            $this->brandService->delete($brand->id); // This should call the service's soft delete method
            return redirect()->route($this->routeNamePrefix . '.index')
                             ->with('success', 'Brand (soft) deleted successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Brand not found for delete: id {$brand->id}}", ['exception' => $e]);
            return redirect()->route($this->routeNamePrefix . '.index')->with('error', 'Brand not found.');
        } catch (\Exception $e) {
            Log::error("Error deleting Brand: id {$brand->id}} " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to delete Brand. Please try again.');
        }
    }

    /**
     * Display a listing of soft-deleted resources.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function trashed(Request $request)
    {
        // Gate::authorize('viewAny', \DryRun\Brands\Models\Brand::class); // Or a specific 'view trashed' permission

        if ($request->ajax()) {
            try {
                $dataTableParams = [
                    'draw' => $request->input('draw'),
                    'start' => $request->input('start', 0),
                    'length' => $request->input('length', 10),
                    'search' => $request->input('search.value', ''),
                    'order' => $request->input('order', [['column' => 0, 'dir' => 'desc']]),
                    'columns' => $request->input('columns', []),
                    'trashed_filter' => 'only', // Force to only show trashed items
                ];

                $results = $this->brandService->getDataForDataTable($dataTableParams); ;

                return response()->json([
                    'draw' => intval($dataTableParams['draw']),
                    'recordsTotal' => $results['total'] ?? 0, // This might need adjustment in service to count only trashed
                    'recordsFiltered' => $results['filtered'] ?? 0,
                    'data' => $results['data'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error("Error fetching trashed brands for DataTables: " . $e->getMessage(), ['exception' => $e]);
                return response()->json(['error' => 'Could not retrieve trashed data.'], 500);
            }
        }
        return view($this->viewPathPrefix . '.trashed', ['isTrashed' => true]); // Pass a flag to the view
    }

    /**
     * Restore the specified soft-deleted resource.
     *
     * @param  string $id  // Use string for ID to handle UUIDs if any, findOrFail will handle it
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(string $id)
    {
        // Find the model instance first to authorize against it
        // The service method should handle finding only trashed models.
        // $brand = $this->brandService->getById($id, [], true); // true for withTrashed
        // Gate::authorize('restore', $brand);
        try {
            $restored = $this->brandService->restore($id);
            if ($restored) {
                return redirect()->route($this->routeNamePrefix . '.index')
                                 ->with('success', 'Brand restored successfully.');
            }
            return back()->with('error', 'Brand could not be restored or was not found in trash.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Brand not found in trash.');
        } catch (\Exception $e) {
            Log::error("Error restoring Brand with ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to restore Brand. Please try again.');
        }
    }

    /**
     * Permanently delete the specified resource.
     *
     * @param  string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDelete(string $id)
    {
        // $brand = $this->brandService->getById($id, [], true); // true for withTrashed
        // Gate::authorize('forceDelete', $brand);
        try {
            $deleted = $this->brandService->forceDelete($id);
            if ($deleted) {
                return redirect()->route($this->routeNamePrefix . '.trashed') // Or index, depending on preference
                                 ->with('success', 'Brand permanently deleted successfully.');
            }
            return back()->with('error', 'Brand could not be permanently deleted or was not found.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Brand not found.');
        } catch (\Exception $e) {
            Log::error("Error force deleting Brand with ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to permanently delete Brand. Please try again.');
        }
    }
}

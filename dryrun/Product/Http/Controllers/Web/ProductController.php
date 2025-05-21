<?php

namespace DryRun\Product\Http\Controllers\Web;

use App\Http\Controllers\Controller; // Assuming global base controller
use DryRun\Product\Models\Product;
use DryRun\Product\Http\Requests\ProductRequest;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

// TODO: Import other models if needed for dropdowns in create/edit views, e.g., use App\Models\User;

class ProductController extends Controller
{
    protected $viewPathPrefix;
    protected $routeNamePrefix;
    

    public function __construct()
    {
        
        // $this->middleware('auth'); // Apply to all methods or specific ones

        // Example of authorizing all resource methods using a policy
        // Ensure YourModelNamePolicy is created and registered
        // $this->authorizeResource(\DryRun\Product\Models\Product::class, 'product');
        // Note: For authorizeResource to work correctly, your route parameter name
        // in routes/web.php for the resource should match the singular model name placeholder.
        // e.g., Route::resource('posts', PostController::class); -> parameter 'post'
        // If your parameter is different, e.g. 'article' for 'Post' model, authorize manually in each method.

        $this->viewPathPrefix = 'product::products';
        $this->routeNamePrefix = 'product.products';
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
        // Gate::authorize('viewAny', \DryRun\Product\Models\Product::class);

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

                $results = ['data'=>[], 'total'=>0, 'filtered'=>0]; // Service not generated, using DryRun\Product\Models\Product; ; // Service method handles data fetching

                return response()->json([
                    'draw' => intval($dataTableParams['draw']),
                    'recordsTotal' => $results['total'] ?? 0,
                    'recordsFiltered' => $results['filtered'] ?? 0,
                    'data' => $results['data'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error("Error fetching products for DataTables: " . $e->getMessage(), ['exception' => $e]);
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
        // Gate::authorize('create', \DryRun\Product\Models\Product::class);

        // TODO: Fetch related data for dropdowns/selects if necessary, e.g., from the service
        // $relatedData = $this->productService->getFormDataForCreate();
        // return view($this->viewPathPrefix . '.create', $relatedData);
        return view($this->viewPathPrefix . '.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \DryRun\Product\Http\Requests\ProductRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ProductRequest $request)
    {
        // Authorization is typically handled by the FormRequest's authorize() method.
        // Or, Gate::authorize('create', \DryRun\Product\Models\Product::class);
        try {
            $product = \DryRun\Product\Models\Product::create($request->validated());
            // TODO: Handle file uploads if any, usually done in the service or before calling service
            // TODO: Handle syncing of BelongsToMany relationships if any, usually done in the service

            return redirect()->route($this->routeNamePrefix . '.index')
                             ->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            Log::error("Error creating Product: " . $e->getMessage(), [
                'request_data' => $request->validated(), // Be cautious logging sensitive data
                'exception' => $e
            ]);
            return back()->withInput()->with('error', 'Failed to create Product. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \DryRun\Product\Models\Product  $product
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(\DryRun\Product\Models\Product $product) // Route model binding
    {
        // Gate::authorize('view', $product);
        try {
            // If using a service to fetch with more relations or logic:
            // $product = $this->productService->getByIdWithDetails($product->id);
            // Ensure the service method throws ModelNotFoundException if not found.

            // Eager load relationships if not already loaded and needed for the view
            // $product->loadMissing(['user', 'comments']); // Example

            return view($this->viewPathPrefix . '.show', compact('product'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Product not found for show: id {$product->id}}", ['exception' => $e]);
            return redirect()->route($this->routeNamePrefix . '.index')->with('error', 'Product not found.');
        } catch (\Exception $e) {
            Log::error("Error showing Product: id {$product->id}} " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Could not display Product. Please try again later.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \DryRun\Product\Models\Product  $product
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(\DryRun\Product\Models\Product $product)
    {
        // Gate::authorize('update', $product);

        // TODO: Fetch related data for dropdowns/selects if necessary
        // $relatedData = $this->productService->getFormDataForEdit($product);
        // return view($this->viewPathPrefix . '.edit', array_merge(compact('product'), $relatedData));
        return view($this->viewPathPrefix . '.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \DryRun\Product\Http\Requests\ProductRequest  $request
     * @param  \DryRun\Product\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProductRequest $request, \DryRun\Product\Models\Product $product)
    {
        // Authorization is typically handled by the FormRequest's authorize() method.
        // Or, Gate::authorize('update', $product);
        try {
            $product = $product->update($request->validated());
            // TODO: Handle file uploads if any, usually done in the service or before calling service
            // TODO: Handle syncing of BelongsToMany relationships if any, usually done in the service

            return redirect()->route($this->routeNamePrefix . '.index')
                             ->with('success', 'Product updated successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Product not found for update: id {$product->id}}", ['request_data' => $request->validated(), 'exception' => $e]);
            return redirect()->route($this->routeNamePrefix . '.index')->with('error', 'Product not found.');
        } catch (\Exception $e) {
            Log::error("Error updating Product: id {$product->id}} " . $e->getMessage(), [
                'request_data' => $request->validated(), // Be cautious logging sensitive data
                'exception' => $e
            ]);
            return back()->withInput()->with('error', 'Failed to update Product. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     *
     * @param  \DryRun\Product\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(\DryRun\Product\Models\Product $product)
    {
        // Gate::authorize('delete', $product);
        try {
            $product->delete(); // This should call the service's soft delete method
            return redirect()->route($this->routeNamePrefix . '.index')
                             ->with('success', 'Product (soft) deleted successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Product not found for delete: id {$product->id}}", ['exception' => $e]);
            return redirect()->route($this->routeNamePrefix . '.index')->with('error', 'Product not found.');
        } catch (\Exception $e) {
            Log::error("Error deleting Product: id {$product->id}} " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to delete Product. Please try again.');
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
        // Gate::authorize('viewAny', \DryRun\Product\Models\Product::class); // Or a specific 'view trashed' permission

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

                $results = ['data'=>[], 'total'=>0, 'filtered'=>0]; // Service not generated, using DryRun\Product\Models\Product; ;

                return response()->json([
                    'draw' => intval($dataTableParams['draw']),
                    'recordsTotal' => $results['total'] ?? 0, // This might need adjustment in service to count only trashed
                    'recordsFiltered' => $results['filtered'] ?? 0,
                    'data' => $results['data'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error("Error fetching trashed products for DataTables: " . $e->getMessage(), ['exception' => $e]);
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
        // $product = $this->productService->getById($id, [], true); // true for withTrashed
        // Gate::authorize('restore', $product);
        try {
            $restored = \DryRun\Product\Models\Product::withTrashed()->find($id)?->restore();
            if ($restored) {
                return redirect()->route($this->routeNamePrefix . '.index')
                                 ->with('success', 'Product restored successfully.');
            }
            return back()->with('error', 'Product could not be restored or was not found in trash.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Product not found in trash.');
        } catch (\Exception $e) {
            Log::error("Error restoring Product with ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to restore Product. Please try again.');
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
        // $product = $this->productService->getById($id, [], true); // true for withTrashed
        // Gate::authorize('forceDelete', $product);
        try {
            $deleted = \DryRun\Product\Models\Product::withTrashed()->find($id)?->forceDelete();
            if ($deleted) {
                return redirect()->route($this->routeNamePrefix . '.trashed') // Or index, depending on preference
                                 ->with('success', 'Product permanently deleted successfully.');
            }
            return back()->with('error', 'Product could not be permanently deleted or was not found.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Product not found.');
        } catch (\Exception $e) {
            Log::error("Error force deleting Product with ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to permanently delete Product. Please try again.');
        }
    }
}

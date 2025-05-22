<?php

namespace DryRun\Products\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DryRun\Products\Models\Product;
use DryRun\Products\Http\Requests\ProductRequest;
use DryRun\Products\Http\Resources\ProductResource;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(name="Products", description="API Endpoints for Products")
 */
class ProductController extends Controller
{
    

    public function __construct()
    {
        
        // $this->middleware('auth:sanctum')->except(['index', 'show']);
        // $this->authorizeResource(\DryRun\Products\Models\Product::class, 'product');
    }

    /**
     * @OA\Get(
     * path="/api/products", operationId="getProductsList", tags={"Products"}, summary="List products",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     * @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     * @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="sort_by", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="sort_direction", in="query", required=false, @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")),
     * @OA\Parameter(name="trashed", in="query", description="Filter by soft delete state (e.g., 'only', 'with', 'without')", required=false, @OA\Schema(type="string", enum={"only", "with", "without"})),
     * @OA\Response(response=200, description="Success", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductResource")), @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"), @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"))),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'status', 'sort_by', 'sort_direction', 'trashed']);
            $perPage = $request->input('per_page', 15);
            // TODO: Ensure service's getAllPaginated handles 'trashed' filter
            $products = \DryRun\Products\Models\Product::latest()->paginate($request->input('per_page', 15));
            return ProductResource::collection($products)->response();
        } catch (\Exception $e) {
            Log::error("API Error fetching products: " . $e->getMessage(), ['exception' => $e, 'filters' => $request->all()]);
            return response()->json(['message' => 'Error retrieving products.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     * path="/api/products", operationId="storeProduct", tags={"Products"}, summary="Create product",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ProductRequest")),
     * @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ProductResource")),
     * @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(ProductRequest $request): JsonResponse
    {
        try {
            $product = \DryRun\Products\Models\Product::create($request->validated());
            return (new ProductResource($product->loadMissing(/* relationships */)))
                        ->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error creating Product: " . $e->getMessage(), ['request_data' => $request->validated(), 'exception' => $e]);
            return response()->json(['message' => 'Failed to create Product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     * path="/api/products/{id}", operationId="getProductById", tags={"Products"}, summary="Get product",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/ProductResource")),
     * @OA\Response(response=404, description="Not Found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(string $id): JsonResponse // Changed to string to handle withTrashed from service
    {
        try {
            // Assuming service's getById can handle finding trashed items if needed, or add a query param
            $product = $this->productService->getById($id, [], request()->has('with_trashed'));
            return (new ProductResource($product->loadMissing(/* relationships */)))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error showing Product {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Error retrieving Product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     * path="/api/products/{id}", operationId="updateProduct", tags={"Products"}, summary="Update product",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ProductRequest")),
     * @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/ProductResource")),
     * @OA\Response(response=404, description="Not Found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(ProductRequest $request, string $id): JsonResponse
    {
        try {
            $product = $product->update($request->validated()); // Service's update should use getById which can find non-trashed
            return (new ProductResource($product->loadMissing(/* relationships */)))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error updating Product {$id}: " . $e->getMessage(), ['request_data' => $request->validated(), 'exception' => $e]);
            return response()->json(['message' => 'Failed to update Product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/products/{id}", operationId="deleteProduct", tags={"Products"}, summary="Delete product (Soft Delete)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\Parameter(name="force", in="query", description="Set to true to force delete permanently", required=false, @OA\Schema(type="boolean")),
     * @OA\Response(response=204, description="Successfully deleted"),
     * @OA\Response(response=404, description="Not Found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            if ($request->query('force', false)) {
                $this->productService->forceDelete($id);
            } else {
                $this->productService->delete($id); // Soft delete
            }
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error deleting Product {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to delete Product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     * path="/api/products/{id}/restore", operationId="restoreProduct", tags={"Products"}, summary="Restore soft-deleted product",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Successfully restored", @OA\JsonContent(ref="#/components/schemas/ProductResource")),
     * @OA\Response(response=404, description="Not Found or not trashed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $this->productService->restore($id);
            $product = $this->productService->getById($id); // Get the restored model
            return (new ProductResource($product))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found or not in trash.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error("API Error restoring Product {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to restore Product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

// Ensure OpenAPI schema definitions are accurate and complete
/**
 * @OA\Schema(schema="ProductRequest", title="Product Request Body", type="object", required={"field1"}, @OA\Property(property="example_field", type="string", example="Sample Value"))
 * @OA\Schema(schema="ErrorResponse", title="Error Response", type="object", @OA\Property(property="message", type="string", example="Error message."))
 * @OA\Schema(schema="ValidationErrorResponse", title="Validation Error Response", type="object", @OA\Property(property="message", type="string", example="The given data was invalid."), @OA\Property(property="errors", type="object", example={"field1": {"Error for field1."}}))
 * @OA\Schema(schema="PaginationLinks", title="Pagination Links", type="object", @OA\Property(property="first", type="string", format="url"), @OA\Property(property="last", type="string", format="url"), @OA\Property(property="prev", type="string", format="url", nullable=true), @OA\Property(property="next", type="string", format="url", nullable=true))
 * @OA\Schema(schema="PaginationMeta", title="Pagination Meta", type="object", @OA\Property(property="current_page", type="integer"), @OA\Property(property="from", type="integer", nullable=true), @OA\Property(property="last_page", type="integer"), @OA\Property(property="path", type="string", format="url"), @OA\Property(property="per_page", type="integer"), @OA\Property(property="to", type="integer", nullable=true), @OA\Property(property="total", type="integer"), @OA\Property(property="links", type="array", @OA\Items(type="object", @OA\Property(property="url", type="string", format="url", nullable=true), @OA\Property(property="label", type="string"), @OA\Property(property="active", type="boolean"))))
 */

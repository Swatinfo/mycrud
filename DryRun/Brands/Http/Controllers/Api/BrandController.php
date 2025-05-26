<?php

namespace DryRun\Brands\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DryRun\Brands\Models\Brand;
use DryRun\Brands\Http\Requests\BrandRequest;
use DryRun\Brands\Http\Resources\BrandResource;
use DryRun\Brands\Contracts\BrandServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(name="Brands", description="API Endpoints for Brands")
 */
class BrandController extends Controller
{
    protected BrandServiceInterface $brandService;

    public function __construct(\DryRun\Brands\Contracts\BrandServiceInterface $brandService)
    {
        $this->brandService = $brandService;
        // $this->middleware('auth:sanctum')->except(['index', 'show']);
        // $this->authorizeResource(\DryRun\Brands\Models\Brand::class, 'brand');
    }

    /**
     * @OA\Get(
     * path="/api/brands", operationId="getBrandsList", tags={"Brands"}, summary="List brands",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     * @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     * @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="sort_by", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="sort_direction", in="query", required=false, @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")),
     * @OA\Parameter(name="trashed", in="query", description="Filter by soft delete state (e.g., 'only', 'with', 'without')", required=false, @OA\Schema(type="string", enum={"only", "with", "without"})),
     * @OA\Response(response=200, description="Success", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BrandResource")), @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"), @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"))),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'status', 'sort_by', 'sort_direction', 'trashed']);
            $perPage = $request->input('per_page', 15);
            // TODO: Ensure service's getAllPaginated handles 'trashed' filter
            $brands = $this->brandService->getAllPaginated($request->all(), $request->input('per_page', 15));
            return BrandResource::collection($brands)->response();
        } catch (\Exception $e) {
            Log::error("API Error fetching brands: " . $e->getMessage(), ['exception' => $e, 'filters' => $request->all()]);
            return response()->json(['message' => 'Error retrieving brands.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     * path="/api/brands", operationId="storeBrand", tags={"Brands"}, summary="Create brand",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BrandRequest")),
     * @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/BrandResource")),
     * @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(BrandRequest $request): JsonResponse
    {
        try {
            $brand = $this->brandService->create($request->validated());
            return (new BrandResource($brand->loadMissing(/* relationships */)))
                        ->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error creating Brand: " . $e->getMessage(), ['request_data' => $request->validated(), 'exception' => $e]);
            return response()->json(['message' => 'Failed to create Brand.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     * path="/api/brands/{id}", operationId="getBrandById", tags={"Brands"}, summary="Get brand",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/BrandResource")),
     * @OA\Response(response=404, description="Not Found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(string $id): JsonResponse // Changed to string to handle withTrashed from service
    {
        try {
            // Assuming service's getById can handle finding trashed items if needed, or add a query param
            $brand = $this->brandService->getById($id, [], request()->has('with_trashed'));
            return (new BrandResource($brand->loadMissing(/* relationships */)))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error showing Brand {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Error retrieving Brand.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     * path="/api/brands/{id}", operationId="updateBrand", tags={"Brands"}, summary="Update brand",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BrandRequest")),
     * @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/BrandResource")),
     * @OA\Response(response=404, description="Not Found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(BrandRequest $request, string $id): JsonResponse
    {
        try {
            $brand = $this->brandService->update($brand->id, $request->validated()); // Service's update should use getById which can find non-trashed
            return (new BrandResource($brand->loadMissing(/* relationships */)))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error updating Brand {$id}: " . $e->getMessage(), ['request_data' => $request->validated(), 'exception' => $e]);
            return response()->json(['message' => 'Failed to update Brand.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/brands/{id}", operationId="deleteBrand", tags={"Brands"}, summary="Delete brand (Soft Delete)",
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
                $this->brandService->forceDelete($id);
            } else {
                $this->brandService->delete($id); // Soft delete
            }
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { /* ... error handling ... */
            Log::error("API Error deleting Brand {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to delete Brand.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     * path="/api/brands/{id}/restore", operationId="restoreBrand", tags={"Brands"}, summary="Restore soft-deleted brand",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Successfully restored", @OA\JsonContent(ref="#/components/schemas/BrandResource")),
     * @OA\Response(response=404, description="Not Found or not trashed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * @OA\Response(response=500, description="Server Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $this->brandService->restore($id);
            $brand = $this->brandService->getById($id); // Get the restored model
            return (new BrandResource($brand))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found or not in trash.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error("API Error restoring Brand {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to restore Brand.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

// Ensure OpenAPI schema definitions are accurate and complete
/**
 * @OA\Schema(schema="BrandRequest", title="Brand Request Body", type="object", required={"field1"}, @OA\Property(property="example_field", type="string", example="Sample Value"))
 * @OA\Schema(schema="ErrorResponse", title="Error Response", type="object", @OA\Property(property="message", type="string", example="Error message."))
 * @OA\Schema(schema="ValidationErrorResponse", title="Validation Error Response", type="object", @OA\Property(property="message", type="string", example="The given data was invalid."), @OA\Property(property="errors", type="object", example={"field1": {"Error for field1."}}))
 * @OA\Schema(schema="PaginationLinks", title="Pagination Links", type="object", @OA\Property(property="first", type="string", format="url"), @OA\Property(property="last", type="string", format="url"), @OA\Property(property="prev", type="string", format="url", nullable=true), @OA\Property(property="next", type="string", format="url", nullable=true))
 * @OA\Schema(schema="PaginationMeta", title="Pagination Meta", type="object", @OA\Property(property="current_page", type="integer"), @OA\Property(property="from", type="integer", nullable=true), @OA\Property(property="last_page", type="integer"), @OA\Property(property="path", type="string", format="url"), @OA\Property(property="per_page", type="integer"), @OA\Property(property="to", type="integer", nullable=true), @OA\Property(property="total", type="integer"), @OA\Property(property="links", type="array", @OA\Items(type="object", @OA\Property(property="url", type="string", format="url", nullable=true), @OA\Property(property="label", type="string"), @OA\Property(property="active", type="boolean"))))
 */

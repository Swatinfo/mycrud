<?php

use Illuminate\Support\Facades\Route;
use DryRun\Product\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes for Product Module
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class)->names('api.product.products');
    // Example: Route::get('/products/custom-action', [ProductController::class, 'customAction'])->name('api.product.products.customAction');
// });

// Fallback route for this module's API (optional)
// Route::fallback(function () {
//     return response()->json(['message' => 'Not Found in Product API'], 404);
// })->name('api.product.products.fallback');


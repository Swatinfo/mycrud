<?php

use Illuminate\Support\Facades\Route;
use DryRun\Brands\Http\Controllers\Api\BrandController;

/*
|--------------------------------------------------------------------------
| API Routes for Brands Module
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('brands', BrandController::class)->names('api.brands.brands');
    // Example: Route::get('/brands/custom-action', [BrandController::class, 'customAction'])->name('api.brands.brands.customAction');
// });

// Fallback route for this module's API (optional)
// Route::fallback(function () {
//     return response()->json(['message' => 'Not Found in Brands API'], 404);
// })->name('api.brands.brands.fallback');


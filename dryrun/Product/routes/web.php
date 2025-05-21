<?php

use Illuminate\Support\Facades\Route;
use DryRun\Product\Http\Controllers\Web\ProductController;

Route::prefix('products')->name('product.products.')->group(function () {
    Route::get('/trashed', [ProductController::class, 'trashed'])->name('trashed'); // New route for trashed items
    Route::post('/{id}/restore', [ProductController::class, 'restore'])->name('restore'); // For soft delete restore
    Route::delete('/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('forceDelete'); // For permanent delete
});

Route::resource('products', ProductController::class)
    ->names('product.products')
    // ->middleware(['auth']);
    ;

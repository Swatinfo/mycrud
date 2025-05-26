<?php

use Illuminate\Support\Facades\Route;
use DryRun\Brands\Http\Controllers\Web\BrandController;

Route::prefix('brands')->name('brands.brands.')->group(function () {
    Route::get('/trashed', [BrandController::class, 'trashed'])->name('trashed'); // New route for trashed items
    Route::post('/{id}/restore', [BrandController::class, 'restore'])->name('restore'); // For soft delete restore
    Route::delete('/{id}/force-delete', [BrandController::class, 'forceDelete'])->name('forceDelete'); // For permanent delete
});

Route::resource('brands', BrandController::class)
    ->names('brands.brands')
    // ->middleware(['auth']);
    ;

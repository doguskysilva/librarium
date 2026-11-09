<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryImportController;
use App\Http\Controllers\ReadingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::resource('books', BookController::class);
    Route::resource('books.readings', ReadingController::class)->only(['create', 'store']);
    Route::resource('readings', ReadingController::class)->only(['show', 'edit', 'update']);
    Route::post('readings/{reading}/start', [ReadingController::class, 'start'])->name('readings.start');
    Route::post('readings/{reading}/pause', [ReadingController::class, 'pause'])->name('readings.pause');
    Route::post('readings/{reading}/resume', [ReadingController::class, 'resume'])->name('readings.resume');
    Route::post('readings/{reading}/complete', [ReadingController::class, 'complete'])->name('readings.complete');
    Route::post('readings/{reading}/skip', [ReadingController::class, 'skip'])->name('readings.skip');
    Route::post('readings/{reading}/abandon', [ReadingController::class, 'abandon'])->name('readings.abandon');
    Route::resource('authors', AuthorController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('collections', CollectionController::class);

    Route::get('inventory/import', [InventoryImportController::class, 'index'])->name('inventory-import.index');
    Route::post('inventory/import/preview', [InventoryImportController::class, 'preview'])->name('inventory-import.preview');
    Route::post('inventory/import/apply', [InventoryImportController::class, 'apply'])->name('inventory-import.apply');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';

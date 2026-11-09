<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PalletAttachmentController;
use App\Http\Controllers\PalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('items/export', [ItemController::class, 'export'])->name('items.export');
    Route::resource('pallets', PalletController::class);
    Route::resource('pallets.items', ItemController::class)->shallow()->except(['index']);
    Route::get('items/{item}/image', [ItemController::class, 'image'])->name('items.image');

    Route::prefix('pallets/{pallet}/attachments')->name('pallets.attachments.')->scopeBindings()->group(function () {
        Route::post('/', [PalletAttachmentController::class, 'store'])->name('store');
        Route::get('{attachment}', [PalletAttachmentController::class, 'show'])->name('show');
        Route::get('{attachment}/download', [PalletAttachmentController::class, 'download'])->name('download');
        Route::delete('{attachment}', [PalletAttachmentController::class, 'destroy'])->name('destroy');
    });

    Route::get('items', [ItemController::class, 'index'])->name('items.index');
    Route::patch('items/{item}/transition', [ItemController::class, 'transition'])->name('items.transition');
    Route::put('items/{item}/inspection', [InspectionController::class, 'update'])->name('items.inspection');
});

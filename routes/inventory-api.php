<?php

use App\Http\Controllers\Api\V1\InventoryAssetController;
use App\Http\Controllers\Api\V1\InventoryCatalogController;
use App\Http\Controllers\Api\V1\InventoryLoanController;
use App\Http\Controllers\Api\V1\InventorySessionController;
use App\Http\Controllers\Api\V1\InventoryStockController;
use App\Http\Controllers\InventoryAssetBatchLabelController;
use App\Http\Controllers\InventoryAssetLabelController;
use App\Http\Controllers\InventoryItemLabelController;
use App\Http\Controllers\InventoryLoanDocumentPreviewController;
use App\Http\Controllers\InventoryLoanEvidenceController;
use App\Http\Controllers\InventoryLoanIssuedDocumentController;
use App\Http\Middleware\EnsureApiAccountActive;
use App\Http\Middleware\ResolveApiSchool;
use Illuminate\Support\Facades\Route;

// Shared mobile API: Bearer authentication and request-scoped X-School-Id.
Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware(['auth:sanctum', EnsureApiAccountActive::class])->group(function (): void {
        Route::get('schools', [InventorySessionController::class, 'schools'])->name('schools');
    });
    Route::prefix('inventory')->name('inventory.')->middleware(['auth:sanctum', EnsureApiAccountActive::class, ResolveApiSchool::class, 'can:inventory.view', 'throttle:120,1,inventory-api'])->group(function (): void {
        Route::get('context', [InventorySessionController::class, 'context'])->name('context');
        Route::get('resolve', [InventoryLoanController::class, 'resolve'])->name('resolve');
        Route::get('stock-balances', [InventoryStockController::class, 'balances'])->name('balances');
        Route::get('stock-movements', [InventoryStockController::class, 'movements'])->middleware('can:inventory.audit.view')->name('movements');
        Route::post('stock-movements', [InventoryStockController::class, 'store'])->name('movements.store');
        Route::get('assets', [InventoryAssetController::class, 'index'])->name('assets');
        Route::post('assets', [InventoryAssetController::class, 'store'])->name('assets.store');
        Route::get('assets/{id}', [InventoryAssetController::class, 'show'])->whereNumber('id')->name('assets.show');
        Route::patch('assets/{id}', [InventoryAssetController::class, 'update'])->whereNumber('id')->name('assets.update');
        Route::get('assets/{id}/events', [InventoryAssetController::class, 'events'])->whereNumber('id')->middleware('can:inventory.audit.view')->name('assets.events');
        Route::get('loans', [InventoryLoanController::class, 'index'])->name('loans');
        Route::post('loans', [InventoryLoanController::class, 'store'])->name('loans.store');
        Route::get('loans/{id}', [InventoryLoanController::class, 'show'])->whereNumber('id')->name('loans.show');
        Route::get('loans/{id}/events', [InventoryLoanController::class, 'events'])->whereNumber('id')->middleware('can:inventory.audit.view')->name('loans.events');
        Route::get('loans/{id}/documents', [InventoryLoanController::class, 'documents'])->whereNumber('id')->name('loans.documents');
        Route::post('loans/{id}/approve', [InventoryLoanController::class, 'approve'])->whereNumber('id')->middleware('can:inventory.loans.approve')->name('loans.approve');
        Route::post('loans/{id}/cancel', [InventoryLoanController::class, 'cancel'])->whereNumber('id')->name('loans.cancel');
        Route::post('loans/{id}/handover', [InventoryLoanController::class, 'transact'])->whereNumber('id')->name('loans.handover');
        Route::post('loans/{id}/return', [InventoryLoanController::class, 'transact'])->whereNumber('id')->name('loans.return');
        Route::get('loans/{loan}/documents/{type}/preview', InventoryLoanDocumentPreviewController::class)->whereNumber('loan')->whereIn('type', ['handover', 'return'])->name('documents.preview');
        Route::post('loans/{loan}/documents/{type}', [InventoryLoanIssuedDocumentController::class, 'issue'])->whereNumber('loan')->whereIn('type', ['handover', 'return'])->middleware('throttle:10,1,inventory-documents')->name('documents.issue');
        Route::get('documents/{document}', [InventoryLoanIssuedDocumentController::class, 'show'])->whereNumber('document')->name('documents.show');
        Route::post('documents/{document}/correct', [InventoryLoanIssuedDocumentController::class, 'correct'])->whereNumber('document')->middleware('throttle:10,1,inventory-documents')->name('documents.correct');
        Route::post('documents/{document}/reprint', [InventoryLoanIssuedDocumentController::class, 'logReprint'])->whereNumber('document')->name('documents.reprint');
        Route::post('events/{event}/evidence', [InventoryLoanEvidenceController::class, 'store'])->whereNumber('event')->middleware('throttle:30,1,inventory-evidence')->name('evidence.store');
        Route::get('evidence/{evidence}', [InventoryLoanEvidenceController::class, 'show'])->whereNumber('evidence')->name('evidence.show');
        Route::get('labels/items/{item}', InventoryItemLabelController::class)->whereNumber('item')->middleware('can:inventory.labels.print')->name('labels.item');
        Route::get('labels/assets/batch', InventoryAssetBatchLabelController::class)->middleware('can:inventory.labels.print')->name('labels.batch');
        Route::get('labels/assets/{asset}', InventoryAssetLabelController::class)->whereNumber('asset')->middleware('can:inventory.labels.print')->name('labels.asset');
        Route::get('{catalog}', [InventoryCatalogController::class, 'index'])->whereIn('catalog', ['categories', 'owners', 'locations', 'items'])->name('catalog.index');
        Route::post('{catalog}', [InventoryCatalogController::class, 'store'])->whereIn('catalog', ['categories', 'owners', 'locations', 'items'])->name('catalog.store');
        Route::get('{catalog}/{id}', [InventoryCatalogController::class, 'show'])->whereIn('catalog', ['categories', 'owners', 'locations', 'items'])->whereNumber('id')->name('catalog.show');
        Route::patch('{catalog}/{id}', [InventoryCatalogController::class, 'update'])->whereIn('catalog', ['categories', 'owners', 'locations', 'items'])->whereNumber('id')->name('catalog.update');
    });
});

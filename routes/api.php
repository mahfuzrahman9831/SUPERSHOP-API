<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\TaxRateController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductBundleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReturnController;

Route::prefix('v1')->group(function () {

    // Auth Routes (Public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        // Products
        Route::get('products/search', [ProductController::class, 'search']);
        Route::get('products/low-stock', [ProductController::class, 'lowStock']);
        Route::apiResource('products', ProductController::class);

        // Brands
        Route::apiResource('brands', BrandController::class);

        // Categories
        Route::apiResource('categories', CategoryController::class);

        // Units
        Route::apiResource('units', UnitController::class);

        // Tax Rates
        Route::apiResource('tax-rates', TaxRateController::class);

        // Product Bundles
        Route::apiResource('product-bundles', ProductBundleController::class);

        // Warehouses
        Route::get('warehouses/{warehouse}/stocks', [WarehouseController::class, 'stocks']);
        Route::apiResource('warehouses', WarehouseController::class);

        // Stock
        Route::post('stock/opening', [StockController::class, 'opening']);
        Route::post('stock/adjust', [StockController::class, 'adjust']);
        Route::get('stock/movements', [StockController::class, 'movements']);
        Route::get('stock/valuation', [StockController::class, 'valuation']);
        Route::get('stock/layers', [StockController::class, 'layers']);
        Route::post('stock/damage', [StockController::class, 'damage']);
        Route::post('stock/transfers', [StockController::class, 'transfer']);
        Route::get('stock/transfers/{stockTransfer}', [StockController::class, 'transferShow']);

        // Suppliers
        Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger']);
        Route::apiResource('suppliers', SupplierController::class);

        // Purchases
        Route::post('purchases/{purchase}/payment', [PurchaseController::class, 'payment']);
        Route::apiResource('purchases', PurchaseController::class);

        // Purchase Returns
        Route::apiResource('purchase-returns', PurchaseReturnController::class)->only(['index', 'store', 'show']);

    });

});
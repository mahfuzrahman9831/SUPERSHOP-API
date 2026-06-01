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
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\HeldSaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerGroupController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\RecurringExpenseController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ReportController;

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


        // Sales
        Route::get('sales/{id}/invoice/pdf', [SaleController::class, 'invoicePdf']);
        Route::get('sales/{id}/invoice',     [SaleController::class, 'invoice']);
        Route::post('sales/{id}/payment',    [SaleController::class, 'collectDue']);
        Route::post('sales/{id}/cancel',     [SaleController::class, 'cancel']);
        Route::apiResource('sales', SaleController::class);

        // Held Sales
        Route::get('held-sales/{id}/resume', [HeldSaleController::class, 'resume']);
        Route::apiResource('held-sales', HeldSaleController::class)->except(['update', 'show']);
    
        // Sale Returns
        Route::post('sale-returns/{saleReturn}/approve', [SaleReturnController::class, 'approve']);
        Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store', 'show']);
    
        // Customers
            Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger']);
            Route::post('customers/{customer}/payment', [CustomerController::class, 'payment']);
            Route::get('customers/{customer}/loyalty', [CustomerController::class, 'loyalty']);
            Route::post('customers/{customer}/loyalty/redeem', [CustomerController::class, 'redeemLoyalty']);
            Route::post('customers/{customer}/toggle-vip', [CustomerController::class, 'toggleVip']);
            Route::post('customers/{customer}/toggle-blacklist', [CustomerController::class, 'toggleBlacklist']);
            Route::apiResource('customers', CustomerController::class);

            // Customer Groups
            Route::apiResource('customer-groups', CustomerGroupController::class);

            // Expense Categories
            Route::apiResource('expense-categories', ExpenseCategoryController::class);

            // Expenses
            Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve']);
            Route::apiResource('expenses', ExpenseController::class);

            // Recurring Expenses
            Route::apiResource('recurring-expenses', RecurringExpenseController::class);

            // Shifts
            Route::post('shifts/open', [ShiftController::class, 'open']);
            Route::post('shifts/close', [ShiftController::class, 'close']);
            Route::get('shifts/current', [ShiftController::class, 'current']);
            Route::post('shifts/cash-in-out', [ShiftController::class, 'cashInOut']);
            Route::get('shifts/{shift}/summary', [ShiftController::class, 'summary']);  
            
            // Reports
            Route::prefix('reports')->group(function () {
            Route::get('dashboard', [ReportController::class, 'dashboard']);
            Route::get('sales', [ReportController::class, 'sales']);
            Route::get('sales/pdf', [ReportController::class, 'salesPdf']);
            Route::get('profit', [ReportController::class, 'profit']);
            Route::get('stock', [ReportController::class, 'stock']);
            Route::get('stock/valuation', [ReportController::class, 'stockValuation']);
            Route::get('stock/dead', [ReportController::class, 'deadStock']);
            Route::get('stock/expiry', [ReportController::class, 'expiry']);
            Route::get('purchases', [ReportController::class, 'purchases']);
            Route::get('expenses', [ReportController::class, 'expenses']);
            Route::get('customers/due', [ReportController::class, 'customerDue']);
            Route::get('suppliers/due', [ReportController::class, 'supplierDue']);
            Route::get('cashier', [ReportController::class, 'cashier']);
                    });
                
                    });

});
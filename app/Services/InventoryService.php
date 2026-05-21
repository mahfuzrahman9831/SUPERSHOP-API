<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStockLayer;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Opening Stock যোগ করো
     */
    public function addOpeningStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        float $purchasePrice,
        int $userId,
        ?string $note = null
    ): void {
        DB::transaction(function () use ($productId, $warehouseId, $quantity, $purchasePrice, $userId, $note) {

            // Stock Layer তৈরি
            ProductStockLayer::create([
                'product_id'         => $productId,
                'warehouse_id'       => $warehouseId,
                'purchase_item_id'   => null,
                'purchase_price'     => $purchasePrice,
                'quantity_in'        => $quantity,
                'quantity_remaining' => $quantity,
                'created_at'         => now(),
            ]);

            // Stock Movement record
            $movementType = StockMovementType::where('name', 'opening_stock')->first();
            StockMovement::create([
                'product_id'       => $productId,
                'warehouse_id'     => $warehouseId,
                'movement_type_id' => $movementType->id,
                'quantity'         => $quantity,
                'note'             => $note ?? 'Opening Stock',
                'created_by'       => $userId,
                'created_at'       => now(),
            ]);

            // Product stock_quantity update (cache)
            Product::where('id', $productId)->increment('stock_quantity', $quantity);

            // Warehouse stock update
            $this->updateWarehouseStock($productId, $warehouseId, $quantity);

            // last_purchase_price update
            Product::where('id', $productId)->update([
                'last_purchase_price' => $purchasePrice,
            ]);
        });
    }

    /**
     * Stock Adjustment করো
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        float $newQuantity,
        int $userId,
        ?string $note = null
    ): void {
        DB::transaction(function () use ($productId, $warehouseId, $newQuantity, $userId, $note) {

            $product      = Product::findOrFail($productId);
            $currentStock = $product->stock_quantity;
            $difference   = $newQuantity - $currentStock;

            if ($difference == 0) return;

            // Movement type
            $movementType = StockMovementType::where('name', 'adjustment')->first();

            if ($difference > 0) {
                // Stock বাড়ছে
                ProductStockLayer::create([
                    'product_id'         => $productId,
                    'warehouse_id'       => $warehouseId,
                    'purchase_price'     => $product->last_purchase_price,
                    'quantity_in'        => $difference,
                    'quantity_remaining' => $difference,
                    'created_at'         => now(),
                ]);
            }

            // Movement record
            StockMovement::create([
                'product_id'       => $productId,
                'warehouse_id'     => $warehouseId,
                'movement_type_id' => $movementType->id,
                'quantity'         => abs($difference),
                'note'             => $note ?? 'Stock Adjustment',
                'created_by'       => $userId,
                'created_at'       => now(),
            ]);

            // Product stock update
            Product::where('id', $productId)->update([
                'stock_quantity' => $newQuantity,
            ]);

            // Warehouse stock update
            $this->updateWarehouseStock($productId, $warehouseId, $difference);
        });
    }

    /**
     * Damage/Expired stock entry
     */
    public function recordDamage(
        int $productId,
        int $warehouseId,
        float $quantity,
        string $type,
        int $userId,
        ?string $note = null
    ): void {
        DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $userId, $note) {

            $movementType = StockMovementType::where('name', $type)->first();

            StockMovement::create([
                'product_id'       => $productId,
                'warehouse_id'     => $warehouseId,
                'movement_type_id' => $movementType->id,
                'quantity'         => $quantity,
                'note'             => $note,
                'created_by'       => $userId,
                'created_at'       => now(),
            ]);

            // Stock কমাও
            Product::where('id', $productId)->decrement('stock_quantity', $quantity);
            $this->updateWarehouseStock($productId, $warehouseId, -$quantity);
        });
    }

    /**
     * Warehouse Stock update helper
     */
    public function updateWarehouseStock(int $productId, int $warehouseId, float $quantity): void
    {
        $warehouseStock = WarehouseStock::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0]
        );

        $warehouseStock->increment('quantity', $quantity);
    }
}
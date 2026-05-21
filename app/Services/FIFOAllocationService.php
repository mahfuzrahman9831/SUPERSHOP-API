<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStockLayer;
use Illuminate\Support\Collection;

class FIFOAllocationService
{
    /**
     * FIFO অনুযায়ী stock layer থেকে allocation করো
     * Return: allocated layers with cost info
     */
    public function allocate(int $productId, int $warehouseId, float $quantity): array
    {
        // FIFO: পুরনো layer আগে
        $layers = ProductStockLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remaining    = $quantity;
        $allocations  = [];
        $totalCost    = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $take = min($layer->quantity_remaining, $remaining);

            $allocations[] = [
                'stock_layer_id' => $layer->id,
                'quantity'       => $take,
                'cost_price'     => $layer->purchase_price,
                'total_cost'     => $take * $layer->purchase_price,
            ];

            $totalCost += $take * $layer->purchase_price;
            $remaining -= $take;
        }

        // Stock কি যথেষ্ট আছে?
        if ($remaining > 0) {
            throw new \Exception("পর্যাপ্ত stock নেই। " . $remaining . " টি কম আছে।");
        }

        // Weighted average cost price
        $weightedAvgCost = $totalCost / $quantity;

        return [
            'allocations'      => $allocations,
            'total_cost'       => $totalCost,
            'weighted_avg_cost' => round($weightedAvgCost, 2),
        ];
    }

    /**
     * Stock layer থেকে quantity কমাও
     */
    public function deductLayers(array $allocations): void
    {
        foreach ($allocations as $allocation) {
            ProductStockLayer::where('id', $allocation['stock_layer_id'])
                ->decrement('quantity_remaining', $allocation['quantity']);
        }
    }

    /**
     * Stock layer এ quantity ফেরত দাও (return এর সময়)
     */
    public function returnToLayer(int $layerId, float $quantity): void
    {
        ProductStockLayer::where('id', $layerId)
            ->increment('quantity_remaining', $quantity);
    }

    /**
     * Real stock calculate করো stock_movements থেকে
     */
    public function getRealStock(int $productId, int $warehouseId): float
    {
        return ProductStockLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity_remaining');
    }
}
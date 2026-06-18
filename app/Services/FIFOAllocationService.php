<?php

namespace App\Services;

use App\Models\ProductStockLayer;

class FIFOAllocationService
{
    public function allocate(int $productId, int $warehouseId, float $quantity, ?int $variantId = null): array
    {
        $remaining = $quantity;
        $allocated = [];

        $layers = ProductStockLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $take = min((float)$layer->quantity_remaining, $remaining);

            $layer->decrement('quantity_remaining', $take);

            $allocated[] = [
                'layer_id'   => $layer->id,
                'quantity'   => $take,
                'cost_price' => $layer->purchase_price,  // 'cost_price' → 'purchase_price'
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception("অপর্যাপ্ত stock। {$remaining} unit এর stock নেই।");
        }

        return $allocated;
    }

    public function deallocate(array $layers): void
    {
        foreach ($layers as $layer) {
            ProductStockLayer::where('id', $layer['stock_layer_id'])
                ->increment('quantity_remaining', $layer['quantity']);
        }
    }
}
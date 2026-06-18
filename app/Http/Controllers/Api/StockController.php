<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductStockLayer;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends ApiController
{
    public function __construct(protected InventoryService $inventoryService) {}

    // Opening Stock
    public function opening(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'quantity'       => 'required|numeric|min:0.01',
            'purchase_price' => 'required|numeric|min:0',
            'note'           => 'nullable|string',
        ]);

        $this->inventoryService->addOpeningStock(
            $request->product_id,
            $request->warehouse_id,
            $request->quantity,
            $request->purchase_price,
            $request->user()->id,
            $request->note
        );

        $product = Product::find($request->product_id);
        return $this->success($product, 'Opening Stock যোগ হয়েছে');
    }

    // Stock Adjustment
    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'new_quantity' => 'required|numeric|min:0',
            'note'         => 'nullable|string',
        ]);

        $this->inventoryService->adjustStock(
            $request->product_id,
            $request->warehouse_id,
            $request->new_quantity,
            $request->user()->id,
            $request->note
        );

        $product = Product::find($request->product_id);
        return $this->success($product, 'Stock Adjustment সফল হয়েছে');
    }

    // Stock Movement History
    public function movements(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $movements = StockMovement::with(['movementType', 'warehouse', 'createdBy'])
            ->where('product_id', $request->product_id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($movements);
    }

    // Stock Valuation
    public function valuation(Request $request): JsonResponse
    {
        $products = Product::with(['unit', 'category'])
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->get()
            ->map(function ($product) {
                $layerValue = ProductStockLayer::where('product_id', $product->id)
                    ->selectRaw('SUM(quantity_remaining * purchase_price) as total_value')
                    ->value('total_value') ?? 0;

                return [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'category'       => $product->category?->name,
                    'unit'           => $product->unit?->short_name,
                    'stock_quantity' => $product->stock_quantity,
                    'purchase_price' => $product->last_purchase_price,
                    'selling_price'  => $product->default_selling_price,
                    'stock_value'    => round($layerValue, 2),
                ];
            });

        $totalValue = $products->sum('stock_value');

        return $this->success([
            'products'    => $products,
            'total_value' => round($totalValue, 2),
        ]);
    }

    // Stock Layers (FIFO)
    public function layers(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $layers = ProductStockLayer::with('warehouse')
            ->where('product_id', $request->product_id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->success($layers);
    }

    // Stock Transfer
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'note'              => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'user_id'           => $request->user()->id,
                'reference_no'      => 'TRF-' . date('YmdHis'),
                'status'            => 'completed',
                'note'              => $request->note,
            ]);

            foreach ($request->items as $item) {
                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                ]);

                // From warehouse থেকে কমাও
                $this->inventoryService->updateWarehouseStock(
                    $item['product_id'],
                    $request->from_warehouse_id,
                    -$item['quantity']
                );

                // To warehouse এ বাড়াও
                $this->inventoryService->updateWarehouseStock(
                    $item['product_id'],
                    $request->to_warehouse_id,
                    $item['quantity']
                );
            }
        });

        return $this->success([], 'Stock Transfer সফল হয়েছে');
    }

    // Transfer Details
    public function transferShow(StockTransfer $stockTransfer): JsonResponse
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'user']);
        return $this->success($stockTransfer);
    }

    // Damage Entry
    public function damage(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity'     => 'required|numeric|min:0.01',
            'type'         => 'required|in:damage,expired,wastage',
            'note'         => 'nullable|string',
        ]);

        $this->inventoryService->recordDamage(
            $request->product_id,
            $request->warehouse_id,
            $request->quantity,
            $request->type,
            $request->user()->id,
            $request->note
        );

        return $this->success([], 'Damage Entry সফল হয়েছে');
    }
}
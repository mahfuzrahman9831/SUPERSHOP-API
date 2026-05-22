<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\Supplier;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $returns = PurchaseReturn::with(['purchase', 'supplier', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($returns);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_id'            => 'required|exists:purchases,id',
            'reason'                 => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.stock_layer_id' => 'nullable|exists:product_stock_layers,id',
        ]);

        $return = DB::transaction(function () use ($request) {

            $purchase    = \App\Models\Purchase::findOrFail($request->purchase_id);
            $totalAmount = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['purchase_price']);
            $invoiceNo   = 'RET-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Purchase Return তৈরি
            $return = PurchaseReturn::create([
                'purchase_id'  => $request->purchase_id,
                'supplier_id'  => $purchase->supplier_id,
                'user_id'      => $request->user()->id,
                'invoice_no'   => $invoiceNo,
                'total_amount' => $totalAmount,
                'reason'       => $request->reason,
                'status'       => 'completed',
            ]);

            $movementType = StockMovementType::where('name', 'purchase_return')->first();

            foreach ($request->items as $item) {
                // Return Item তৈরি
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'         => $item['product_id'],
                    'stock_layer_id'     => $item['stock_layer_id'] ?? null,
                    'quantity'           => $item['quantity'],
                    'purchase_price'     => $item['purchase_price'],
                    'total'              => $item['quantity'] * $item['purchase_price'],
                ]);

                // Stock Movement record
                StockMovement::create([
                    'product_id'       => $item['product_id'],
                    'warehouse_id'     => $purchase->warehouse_id,
                    'movement_type_id' => $movementType->id,
                    'quantity'         => $item['quantity'],
                    'reference_type'   => PurchaseReturn::class,
                    'reference_id'     => $return->id,
                    'created_by'       => $request->user()->id,
                    'created_at'       => now(),
                ]);

                // Stock কমাও
                Product::where('id', $item['product_id'])
                    ->decrement('stock_quantity', $item['quantity']);

                // Warehouse stock কমাও
                WarehouseStock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->decrement('quantity', $item['quantity']);
            }

            // Supplier due কমাও
            Supplier::where('id', $purchase->supplier_id)
                ->decrement('total_due', $totalAmount);

            return $return->load(['purchase', 'supplier', 'items.product']);
        });

        return $this->success($return, 'Purchase Return সফল হয়েছে', 201);
    }

    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $purchaseReturn->load(['purchase', 'supplier', 'user', 'items.product']);
        return $this->success($purchaseReturn);
    }
}
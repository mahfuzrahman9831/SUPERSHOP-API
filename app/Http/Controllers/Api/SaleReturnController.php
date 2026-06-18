<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStockLayer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnItemLayer;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $returns = SaleReturn::with(['sale', 'warehouse', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($returns);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id'                    => 'required|exists:sales,id',
            'warehouse_id'               => 'required|exists:warehouses,id',
            'refund_method'              => 'required|in:cash,store_credit,exchange',
            'reason'                     => 'nullable|string',
            'items'                      => 'required|array|min:1',
            'items.*.sale_item_id'       => 'required|exists:sale_items,id',
            'items.*.product_id'         => 'required|exists:products,id',
            'items.*.quantity'           => 'required|numeric|min:0.01',
            'items.*.selling_price'      => 'required|numeric|min:0',
            'items.*.cost_price'         => 'required|numeric|min:0',
        ]);

        $return = DB::transaction(function () use ($request) {

            $sale        = Sale::findOrFail($request->sale_id);
            $totalAmount = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['selling_price']);
            $invoiceNo   = 'RET-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Sale Return তৈরি
            $saleReturn = SaleReturn::create([
                'sale_id'       => $request->sale_id,
                'warehouse_id'  => $request->warehouse_id,
                'user_id'       => $request->user()->id,
                'invoice_no'    => $invoiceNo,
                'total_amount'  => $totalAmount,
                'refund_method' => $request->refund_method,
                'reason'        => $request->reason,
                'status'        => 'completed',
            ]);

            $movementType = StockMovementType::where('name', 'sale_return')->first();

            foreach ($request->items as $item) {

                // Return Item তৈরি
                $returnItem = SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id'   => $item['sale_item_id'],
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'selling_price'  => $item['selling_price'],
                    'cost_price'     => $item['cost_price'],
                    'total'          => $item['quantity'] * $item['selling_price'],
                ]);

                // SaleItem এর layers থেকে FIFO reverse করো
                $saleItem = SaleItem::with('layers')->find($item['sale_item_id']);
                $remaining = $item['quantity'];

                // LIFO order এ layer এ ফেরত দাও (সবচেয়ে নতুন layer আগে)
                foreach ($saleItem->layers()->orderBy('id', 'desc')->get() as $layer) {
                    if ($remaining <= 0) break;

                    $returnQty = min($layer->quantity, $remaining);

                    // Return Item Layer তৈরি
                    SaleReturnItemLayer::create([
                        'sale_return_item_id' => $returnItem->id,
                        'stock_layer_id'      => $layer->stock_layer_id,
                        'quantity'            => $returnQty,
                        'cost_price'          => $layer->cost_price,
                        'total_cost'          => $returnQty * $layer->cost_price,
                    ]);

                    // Stock Layer এ quantity ফেরত দাও
                    ProductStockLayer::where('id', $layer->stock_layer_id)
                        ->increment('quantity_remaining', $returnQty);

                    $remaining -= $returnQty;
                }

                // Stock Movement record
                StockMovement::create([
                    'product_id'       => $item['product_id'],
                    'warehouse_id'     => $request->warehouse_id,
                    'movement_type_id' => $movementType->id,
                    'quantity'         => $item['quantity'],
                    'reference_type'   => SaleReturn::class,
                    'reference_id'     => $saleReturn->id,
                    'created_by'       => $request->user()->id,
                    'created_at'       => now(),
                ]);

                // Product stock বাড়াও
                Product::where('id', $item['product_id'])
                    ->increment('stock_quantity', $item['quantity']);

                // Warehouse stock বাড়াও
                WarehouseStock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $request->warehouse_id)
                    ->increment('quantity', $item['quantity']);
            }

            // Refund payment
            if ($request->refund_method === 'cash') {
                $account = Account::where('is_default', true)->first();

                Payment::create([
                    'payable_type'      => SaleReturn::class,
                    'payable_id'        => $saleReturn->id,
                    'payment_method_id' => 1,
                    'account_id'        => $account->id,
                    'type'              => 'refund',
                    'amount'            => $totalAmount,
                    'paid_at'           => now(),
                    'created_by'        => $request->user()->id,
                ]);

                // Account balance কমাও (cash refund)
                $account->decrement('balance', $totalAmount);

                AccountTransaction::create([
                    'account_id'       => $account->id,
                    'type'             => 'debit',
                    'amount'           => $totalAmount,
                    'balance_after'    => $account->fresh()->balance,
                    'transaction_date' => now()->toDateString(),
                    'reference_type'   => SaleReturn::class,
                    'reference_id'     => $saleReturn->id,
                    'note'             => 'Sale Return Refund - ' . $invoiceNo,
                ]);
            }

            // Customer due কমাও (যদি due sale ছিল)
            if ($sale->customer_id && $sale->due_amount > 0) {
                Customer::where('id', $sale->customer_id)
                    ->decrement('total_due', min($totalAmount, $sale->due_amount));
            }

            // Sale profit update
            $returnProfit = collect($request->items)->sum(fn($i) =>
                ($i['selling_price'] - $i['cost_price']) * $i['quantity']
            );

            Sale::where('id', $request->sale_id)
                ->decrement('profit', $returnProfit);

            return $saleReturn->load(['sale', 'warehouse', 'items.product', 'items.layers']);
        });

        return $this->success($return, 'Sale Return সফল হয়েছে', 201);
    }

    public function show(SaleReturn $saleReturn): JsonResponse
    {
        $saleReturn->load(['sale', 'warehouse', 'user', 'items.product', 'items.layers.stockLayer']);
        return $this->success($saleReturn);
    }

    public function approve(SaleReturn $saleReturn): JsonResponse
    {
        if ($saleReturn->status !== 'pending') {
            return $this->error('শুধু pending return approve করা যাবে', 400);
        }

        $saleReturn->update(['status' => 'approved']);
        return $this->success($saleReturn, 'Sale Return approved হয়েছে');
    }
}
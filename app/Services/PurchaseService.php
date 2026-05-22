<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStockLayer;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\Supplier;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Purchase Complete করো (DB::transaction)
     */
    public function completePurchase(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {

            // Invoice Number generate
            $invoiceNo = $this->generateInvoiceNo();

            // Purchase তৈরি
            $purchase = Purchase::create([
                'supplier_id'    => $data['supplier_id'],
                'warehouse_id'   => $data['warehouse_id'],
                'user_id'        => $userId,
                'invoice_no'     => $invoiceNo,
                'subtotal'       => $data['subtotal'],
                'discount'       => $data['discount'] ?? 0,
                'tax'            => $data['tax'] ?? 0,
                'total_amount'   => $data['total_amount'],
                'paid_amount'    => $data['paid_amount'] ?? 0,
                'due_amount'     => $data['total_amount'] - ($data['paid_amount'] ?? 0),
                'payment_status' => $data['paid_amount'] >= $data['total_amount'] ? 'paid' : ($data['paid_amount'] > 0 ? 'partial' : 'unpaid'),
                'status'         => 'completed',
                'note'           => $data['note'] ?? null,
            ]);

            // Purchase Items + Stock Layer তৈরি
            $movementType = StockMovementType::where('name', 'purchase')->first();

            foreach ($data['items'] as $item) {
                // Stock Layer তৈরি (FIFO)
                $layer = ProductStockLayer::create([
                    'product_id'         => $item['product_id'],
                    'warehouse_id'       => $data['warehouse_id'],
                    'purchase_price'     => $item['purchase_price'],
                    'quantity_in'        => $item['quantity'],
                    'quantity_remaining' => $item['quantity'],
                    'created_at'         => now(),
                ]);

                // Purchase Item তৈরি
                $purchaseItem = PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $item['product_id'],
                    'batch_no'          => $item['batch_no'] ?? null,
                    'expiry_date'       => $item['expiry_date'] ?? null,
                    'quantity'          => $item['quantity'],
                    'received_quantity' => $item['quantity'],
                    'purchase_price'    => $item['purchase_price'],
                    'total'             => $item['quantity'] * $item['purchase_price'],
                    'stock_layer_id'    => $layer->id,
                ]);

                // Stock Layer এ purchase_item_id update
                $layer->update(['purchase_item_id' => $purchaseItem->id]);

                // Stock Movement record
                StockMovement::create([
                    'product_id'       => $item['product_id'],
                    'warehouse_id'     => $data['warehouse_id'],
                    'movement_type_id' => $movementType->id,
                    'quantity'         => $item['quantity'],
                    'reference_type'   => Purchase::class,
                    'reference_id'     => $purchase->id,
                    'created_by'       => $userId,
                    'created_at'       => now(),
                ]);

                // Product stock_quantity UPDATE (cache)
                Product::where('id', $item['product_id'])
                    ->increment('stock_quantity', $item['quantity']);

                // Product last_purchase_price UPDATE
                Product::where('id', $item['product_id'])
                    ->update(['last_purchase_price' => $item['purchase_price']]);

                // Warehouse stock UPDATE
                $warehouseStock = WarehouseStock::firstOrCreate(
                    ['product_id' => $item['product_id'], 'warehouse_id' => $data['warehouse_id']],
                    ['quantity' => 0]
                );
                $warehouseStock->increment('quantity', $item['quantity']);
            }

            // Payment record (যদি paid_amount > 0)
            if (($data['paid_amount'] ?? 0) > 0) {
                $account = Account::where('is_default', true)->first();

                Payment::create([
                    'payable_type'      => Purchase::class,
                    'payable_id'        => $purchase->id,
                    'payment_method_id' => $data['payment_method_id'] ?? 1,
                    'account_id'        => $account->id,
                    'type'              => 'paid',
                    'amount'            => $data['paid_amount'],
                    'transaction_id'    => $data['transaction_id'] ?? null,
                    'paid_at'           => now(),
                    'created_by'        => $userId,
                ]);

                // Account balance UPDATE
                $account->decrement('balance', $data['paid_amount']);

                // Account Transaction record
                AccountTransaction::create([
                    'account_id'       => $account->id,
                    'type'             => 'debit',
                    'amount'           => $data['paid_amount'],
                    'balance_after'    => $account->fresh()->balance,
                    'transaction_date' => now()->toDateString(),
                    'reference_type'   => Purchase::class,
                    'reference_id'     => $purchase->id,
                    'note'             => 'Purchase Payment - ' . $purchase->invoice_no,
                ]);
            }

            // Supplier total_due UPDATE
            if ($purchase->due_amount > 0) {
                Supplier::where('id', $data['supplier_id'])
                    ->increment('total_due', $purchase->due_amount);
            }

            return $purchase->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    /**
     * Purchase Payment (partial/full)
     */
    public function addPayment(Purchase $purchase, array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $userId) {

            $account = Account::findOrFail($data['account_id'] ?? Account::where('is_default', true)->first()->id);

            Payment::create([
                'payable_type'      => Purchase::class,
                'payable_id'        => $purchase->id,
                'payment_method_id' => $data['payment_method_id'],
                'account_id'        => $account->id,
                'type'              => 'paid',
                'amount'            => $data['amount'],
                'transaction_id'    => $data['transaction_id'] ?? null,
                'note'              => $data['note'] ?? null,
                'paid_at'           => now(),
                'created_by'        => $userId,
            ]);

            // Purchase paid_amount + due_amount UPDATE
            $newPaid = $purchase->paid_amount + $data['amount'];
            $newDue  = $purchase->total_amount - $newPaid;

            $purchase->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => max(0, $newDue),
                'payment_status' => $newDue <= 0 ? 'paid' : 'partial',
            ]);

            // Supplier due UPDATE
            Supplier::where('id', $purchase->supplier_id)
                ->decrement('total_due', $data['amount']);

            // Account balance UPDATE
            $account->decrement('balance', $data['amount']);

            AccountTransaction::create([
                'account_id'       => $account->id,
                'type'             => 'debit',
                'amount'           => $data['amount'],
                'balance_after'    => $account->fresh()->balance,
                'transaction_date' => now()->toDateString(),
                'reference_type'   => Purchase::class,
                'reference_id'     => $purchase->id,
                'note'             => 'Purchase Payment - ' . $purchase->invoice_no,
            ]);

            return $purchase->fresh()->load(['supplier', 'items.product', 'payments']);
        });
    }

    /**
     * Invoice Number generate
     */
    private function generateInvoiceNo(): string
    {
        $counter = \App\Models\InvoiceCounter::where('module', 'purchase')
            ->lockForUpdate()
            ->first();

        $counter->increment('current_number');
        $counter->refresh();

        return $counter->prefix . '-' . date('Y') . '-' . str_pad($counter->current_number, 5, '0', STR_PAD_LEFT);
    }
}
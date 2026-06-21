<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemLayer;
use App\Models\ProductStockLayer;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\Customer;
use App\Models\LoyaltyPoint;
use App\Models\HeldSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SaleService
{
    public function __construct(
        protected FIFOAllocationService $fifoService,
        protected InvoiceService        $invoiceService,
        protected PaymentService        $paymentService,
    ) {}

    // ─── Sale Create ─────────────────────────────────────────────────────────

    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            // Invoice Number
            $invoiceNo = $this->invoiceService->generate('sale');

            // Movement Type ID (sale = out)
            $movementTypeId = StockMovementType::where('name', 'sale')->value('id');

            // Totals
            $subtotal       = 0;
            $totalTax       = 0;
            $totalDiscount  = (float)($data['discount_amount'] ?? 0);
            $totalProfit    = 0;

            foreach ($data['items'] as $item) {
                $qty        = (float)$item['quantity'];
                $price      = (float)$item['unit_price'];
                $cost       = (float)($item['cost_price'] ?? 0);
                $subtotal  += $qty * $price;
                $totalTax  += (float)($item['tax_amount'] ?? 0);
                $totalProfit += ($price - $cost) * $qty;
            }

            $grandTotal = $subtotal + $totalTax - $totalDiscount;
            $paidAmount = collect($data['payments'] ?? [])->sum('amount');
            $dueAmount  = max(0, $grandTotal - $paidAmount);

            // ── Sale Insert ──
            $sale = Sale::create([
                'invoice_no'     => $invoiceNo,
                'customer_id'    => $data['customer_id'] ?? null,
                'warehouse_id'   => $data['warehouse_id'],
                'user_id'        => Auth::id(),
                'shift_id'       => $data['shift_id'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => $totalDiscount,
                'tax'            => $totalTax,
                'total_amount'   => $grandTotal,
                'paid_amount'    => $paidAmount,
                'due_amount'     => $dueAmount,
                'profit'         => $totalProfit,
                'payment_status' => $this->resolvePaymentStatus($grandTotal, $paidAmount),
                'status'         => 'completed',
                'note'           => $data['note'] ?? null,
            ]);

            // ── Items + FIFO + Stock Movement ──
            foreach ($data['items'] as $itemData) {
                $qty   = (float)$itemData['quantity'];
                $price = (float)$itemData['unit_price'];
                $cost  = (float)($itemData['cost_price'] ?? 0);
                $tax   = (float)($itemData['tax_amount'] ?? 0);
                $disc  = (float)($itemData['discount_amount'] ?? 0);
                $total = ($qty * $price) + $tax - $disc;
                $profit = ($price - $cost) * $qty;

                $saleItem = SaleItem::create([
                    'sale_id'       => $sale->id,
                    'product_id'    => $itemData['product_id'],
                    'variant_id'    => $itemData['variant_id'] ?? null,
                    'tax_rate_id'   => $itemData['tax_rate_id'] ?? null,
                    'quantity'      => $qty,
                    'selling_price' => $price,
                    'cost_price'    => $cost,
                    'discount'      => $disc,
                    'tax'           => $tax,
                    'profit'        => $profit,
                    'total'         => $total,
                ]);

                // FIFO Allocation
                $layers = $this->fifoService->allocate(
                    productId:   $itemData['product_id'],
                    warehouseId: $data['warehouse_id'],
                    quantity:    $qty,
                    variantId:   $itemData['variant_id'] ?? null,
                );

                foreach ($layers as $layer) {
                    SaleItemLayer::create([
                        'sale_item_id'   => $saleItem->id,
                        'stock_layer_id' => $layer['layer_id'],   // product_stock_layer_id → stock_layer_id
                        'quantity'       => $layer['quantity'],
                        'cost_price'     => $layer['cost_price'],
                        'total_cost'     => $layer['quantity'] * $layer['cost_price'],
                    ]);
                }

                
              // Stock Movement
                StockMovement::create([
                    'product_id'       => $itemData['product_id'],
                    'warehouse_id'     => $data['warehouse_id'],
                    'movement_type_id' => $movementTypeId,         // 'movement_type' string না, FK id
                    'quantity'         => $qty,
                    'reference_type'   => 'sale',
                    'reference_id'     => $sale->id,
                    'note'             => "Sale: {$invoiceNo}",
                    'created_by'       => Auth::id(),
                ]);

                // Product এর cached stock_quantity sync করুন (Products list/POS এর জন্য জরুরি)
                \App\Models\Product::where('id', $itemData['product_id'])->decrement('stock_quantity', $qty);
            }

            // ── Payments ──
            foreach ($data['payments'] ?? [] as $paymentData) {
                $this->paymentService->create([
                    'payable_type'      => 'sale',
                    'payable_id'        => $sale->id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'amount'            => $paymentData['amount'],
                    'reference'         => $paymentData['reference'] ?? null,
                    'paid_at'           => Carbon::now(),
                    'created_by'        => Auth::id(),
                ]);
            }

            // ── Customer Due + Loyalty ──
            if ($sale->customer_id) {
                if ($dueAmount > 0) {
                    Customer::where('id', $sale->customer_id)
                        ->increment('total_due', $dueAmount);
                }
                $this->awardLoyaltyPoints($sale);
            }

            return $sale->load(['items.product', 'payments', 'customer']);
        });
    }

    // ─── Due Collection ──────────────────────────────────────────────────────

    public function collectDue(int $saleId, array $payments): Sale
    {
        return DB::transaction(function () use ($saleId, $payments) {
            $sale = Sale::lockForUpdate()->findOrFail($saleId);

            if ($sale->due_amount <= 0) {
                throw new \Exception('এই sale এ কোনো due নেই।');
            }

            $totalPaying = collect($payments)->sum('amount');

            foreach ($payments as $paymentData) {
                $this->paymentService->create([
                    'payable_type'      => 'sale',
                    'payable_id'        => $sale->id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'amount'            => $paymentData['amount'],
                    'reference'         => $paymentData['reference'] ?? null,
                    'paid_at'           => Carbon::now(),
                    'created_by'        => Auth::id(),
                ]);
            }

            $newPaid = $sale->paid_amount + $totalPaying;
            $newDue  = max(0, $sale->total_amount - $newPaid);

            $sale->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'payment_status' => $this->resolvePaymentStatus($sale->total_amount, $newPaid),
            ]);

            if ($sale->customer_id) {
                Customer::where('id', $sale->customer_id)
                    ->decrement('total_due', min($totalPaying, $sale->due_amount));
            }

            return $sale->fresh();
        });
    }

    // ─── Hold Sale ───────────────────────────────────────────────────────────

    public function holdSale(array $data): HeldSale
    {
        return HeldSale::create([
            'user_id'     => Auth::id(),
            'customer_id' => $data['customer_id'] ?? null,
            'reference'   => 'HOLD-' . time(),             // reference কলাম আছে, NOT NULL
            'cart_data'   => $data['cart'],                 // JSON cast করবে Model
            'note'        => $data['note'] ?? null,
        ]);
    }

    public function resumeHeldSale(int $heldSaleId): array
    {
        $held = HeldSale::where('user_id', Auth::id())->findOrFail($heldSaleId);
        $cart = is_array($held->cart_data) ? $held->cart_data : json_decode($held->cart_data, true);
        $held->delete();
        return $cart;
    }

    // ─── Cancel Sale ─────────────────────────────────────────────────────────

    public function cancelSale(int $saleId, string $reason = ''): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = Sale::with('items.layers')->findOrFail($saleId);

            if ($sale->status === 'cancelled') {
                throw new \Exception('Sale ইতিমধ্যে cancelled।');
            }

            $movementTypeId = StockMovementType::where('name', 'sale_return')->value('id')
                           ?? StockMovementType::where('name', 'adjustment')->value('id');

            foreach ($sale->items as $item) {
                // Stock Layer Restore
                foreach ($item->layers as $layer) {
                    ProductStockLayer::where('id', $layer->stock_layer_id)
                        ->increment('quantity_remaining', $layer->quantity);
                }

                StockMovement::create([
                    'product_id'       => $item->product_id,
                    'warehouse_id'     => $sale->warehouse_id,
                    'movement_type_id' => $movementTypeId,
                    'quantity'         => $item->quantity,
                    'reference_type'   => 'sale_cancel',
                    'reference_id'     => $sale->id,
                    'note'             => "Cancel: {$sale->invoice_no}. {$reason}",
                    'created_by'       => Auth::id(),
                ]);
            }

            if ($sale->customer_id && $sale->due_amount > 0) {
                Customer::where('id', $sale->customer_id)
                    ->decrement('total_due', $sale->due_amount);
            }

            $sale->update([
                'status' => 'cancelled',
                'note'   => trim($sale->note . " | Cancelled: {$reason}"),
            ]);

            return $sale->fresh();
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    protected function awardLoyaltyPoints(Sale $sale): void
    {
        $pointsPerTaka = (float) setting('loyalty_points_per_taka', 0);
        if ($pointsPerTaka <= 0) return;

        $points = (int)($sale->total_amount * $pointsPerTaka);
        if ($points <= 0) return;

        LoyaltyPoint::create([
            'customer_id'    => $sale->customer_id,
            'type'           => 'earn',
            'points'         => $points,
            'reference_type' => 'sale',
            'reference_id'   => $sale->id,
            'note'           => "Sale {$sale->invoice_no}",
        ]);

        Customer::where('id', $sale->customer_id)
            ->increment('loyalty_points', $points);
    }

    protected function resolvePaymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0)      return 'unpaid';
        if ($paid >= $total) return 'paid';
        return 'partial';
    }
}
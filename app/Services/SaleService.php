<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemLayer;
use App\Models\ProductStockLayer;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\LoyaltyPoint;
use App\Models\HeldSale;
use App\Services\FIFOAllocationService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SaleService
{
    public function __construct(
        protected FIFOAllocationService $fifoService,
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService,
    ) {}

    /**
     * সম্পূর্ণ Sale তৈরি — FIFO + Stock + Payment + Loyalty
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            // 1. Invoice Number Generate
            $invoiceNumber = $this->invoiceService->generate('sale');

            // 2. Totals Calculate
            $subtotal     = 0;
            $totalTax     = 0;
            $totalDiscount = $data['discount_amount'] ?? 0;

            foreach ($data['items'] as $item) {
                $subtotal  += $item['unit_price'] * $item['quantity'];
                $totalTax  += $item['tax_amount'] ?? 0;
            }

            $grandTotal = $subtotal + $totalTax - $totalDiscount;
            $paidAmount = collect($data['payments'] ?? [])->sum('amount');
            $dueAmount  = max(0, $grandTotal - $paidAmount);

            // 3. Sale Record Insert
            $sale = Sale::create([
                'invoice_number'  => $invoiceNumber,
                'sale_date'       => $data['sale_date'] ?? Carbon::now(),
                'customer_id'     => $data['customer_id'] ?? null,
                'warehouse_id'    => $data['warehouse_id'],
                'user_id'         => Auth::id(),
                'shift_id'        => $data['shift_id'] ?? null,
                'subtotal'        => $subtotal,
                'tax_amount'      => $totalTax,
                'discount_amount' => $totalDiscount,
                'discount_type'   => $data['discount_type'] ?? 'fixed',
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount,
                'payment_status'  => $this->resolvePaymentStatus($grandTotal, $paidAmount),
                'sale_status'     => 'completed',
                'notes'           => $data['notes'] ?? null,
            ]);

            // 4. Items + FIFO Allocation
            foreach ($data['items'] as $itemData) {
                $saleItem = SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $itemData['product_id'],
                    'variant_id'     => $itemData['variant_id'] ?? null,
                    'quantity'       => $itemData['quantity'],
                    'unit_price'     => $itemData['unit_price'],
                    'cost_price'     => $itemData['cost_price'] ?? 0,
                    'tax_amount'     => $itemData['tax_amount'] ?? 0,
                    'discount_amount'=> $itemData['discount_amount'] ?? 0,
                    'subtotal'       => $itemData['unit_price'] * $itemData['quantity'],
                ]);

                // FIFO Layer থেকে Stock কাটো
                $layers = $this->fifoService->allocate(
                    productId:   $itemData['product_id'],
                    warehouseId: $data['warehouse_id'],
                    quantity:    $itemData['quantity'],
                    variantId:   $itemData['variant_id'] ?? null,
                );

                foreach ($layers as $layer) {
                    SaleItemLayer::create([
                        'sale_item_id'         => $saleItem->id,
                        'product_stock_layer_id'=> $layer['layer_id'],
                        'quantity'             => $layer['quantity'],
                        'cost_price'           => $layer['cost_price'],
                    ]);
                }

                // Stock Movement Insert
                StockMovement::create([
                    'product_id'   => $itemData['product_id'],
                    'variant_id'   => $itemData['variant_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'],
                    'movement_type'=> 'sale',
                    'reference_type'=> Sale::class,
                    'reference_id' => $sale->id,
                    'quantity'     => -$itemData['quantity'],
                    'notes'        => "Sale: {$invoiceNumber}",
                    'created_by'   => Auth::id(),
                ]);
            }

            // 5. Payments Insert
            if (!empty($data['payments'])) {
                foreach ($data['payments'] as $paymentData) {
                    $this->paymentService->create([
                        'payable_type'      => Sale::class,
                        'payable_id'        => $sale->id,
                        'payment_method_id' => $paymentData['payment_method_id'],
                        'amount'            => $paymentData['amount'],
                        'reference'         => $paymentData['reference'] ?? null,
                        'paid_at'           => Carbon::now(),
                        'created_by'        => Auth::id(),
                    ]);
                }
            }

            // 6. Customer Due Update + Loyalty Points
            if ($sale->customer_id) {
                Customer::where('id', $sale->customer_id)
                    ->increment('total_due', $dueAmount);

                $this->awardLoyaltyPoints($sale);
            }

            return $sale->load(['items.product', 'payments', 'customer']);
        });
    }

    /**
     * Loyalty Points Award
     */
    protected function awardLoyaltyPoints(Sale $sale): void
    {
        $pointsPerTaka = setting('loyalty_points_per_taka', 0);
        if ($pointsPerTaka <= 0) return;

        $points = (int) ($sale->grand_total * $pointsPerTaka);
        if ($points <= 0) return;

        LoyaltyPoint::create([
            'customer_id' => $sale->customer_id,
            'sale_id'     => $sale->id,
            'points'      => $points,
            'type'        => 'earn',
            'note'        => "Sale {$sale->invoice_number}",
        ]);

        Customer::where('id', $sale->customer_id)
            ->increment('loyalty_points', $points);
    }

    /**
     * Payment Status Resolve
     */
    protected function resolvePaymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0)          return 'unpaid';
        if ($paid >= $total)     return 'paid';
        return 'partial';
    }

    /**
     * Sale Hold (Cart Save)
     */
    public function holdSale(array $data): HeldSale
    {
        return HeldSale::create([
            'user_id'     => Auth::id(),
            'shift_id'    => $data['shift_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'cart_data'   => json_encode($data['cart']),
            'note'        => $data['note'] ?? null,
        ]);
    }

    /**
     * Held Sale Resume করে Delete
     */
    public function resumeHeldSale(int $heldSaleId): array
    {
        $held = HeldSale::findOrFail($heldSaleId);
        $cart = json_decode($held->cart_data, true);
        $held->delete();
        return $cart;
    }

    /**
     * Due Payment Collect
     */
    public function collectDue(int $saleId, array $payments): Sale
    {
        return DB::transaction(function () use ($saleId, $payments) {
            $sale = Sale::findOrFail($saleId);

            $totalPaying = collect($payments)->sum('amount');

            foreach ($payments as $paymentData) {
                $this->paymentService->create([
                    'payable_type'      => Sale::class,
                    'payable_id'        => $sale->id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'amount'            => $paymentData['amount'],
                    'reference'         => $paymentData['reference'] ?? null,
                    'paid_at'           => Carbon::now(),
                    'created_by'        => Auth::id(),
                ]);
            }

            $newPaid = $sale->paid_amount + $totalPaying;
            $newDue  = max(0, $sale->grand_total - $newPaid);

            $sale->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'payment_status' => $this->resolvePaymentStatus($sale->grand_total, $newPaid),
            ]);

            if ($sale->customer_id) {
                Customer::where('id', $sale->customer_id)
                    ->decrement('total_due', min($totalPaying, $sale->due_amount));
            }

            return $sale->fresh();
        });
    }

    /**
     * Sale Cancel (Stock Restore)
     */
    public function cancelSale(int $saleId, string $reason = ''): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = Sale::with('items.layers')->findOrFail($saleId);

            if ($sale->sale_status === 'cancelled') {
                throw new \Exception('Sale already cancelled.');
            }

            // Stock Layer Restore
            foreach ($sale->items as $item) {
                foreach ($item->layers as $layer) {
                    ProductStockLayer::where('id', $layer->product_stock_layer_id)
                        ->increment('quantity_remaining', $layer->quantity);
                }

                StockMovement::create([
                    'product_id'    => $item->product_id,
                    'variant_id'    => $item->variant_id,
                    'warehouse_id'  => $sale->warehouse_id,
                    'movement_type' => 'sale_cancel',
                    'reference_type'=> Sale::class,
                    'reference_id'  => $sale->id,
                    'quantity'      => $item->quantity,
                    'notes'         => "Cancel: {$sale->invoice_number}. {$reason}",
                    'created_by'    => Auth::id(),
                ]);
            }

            // Customer Due Restore
            if ($sale->customer_id && $sale->due_amount > 0) {
                Customer::where('id', $sale->customer_id)
                    ->decrement('total_due', $sale->due_amount);
            }

            $sale->update([
                'sale_status' => 'cancelled',
                'notes'       => $sale->notes . " | Cancelled: {$reason}",
            ]);

            return $sale->fresh();
        });
    }
}
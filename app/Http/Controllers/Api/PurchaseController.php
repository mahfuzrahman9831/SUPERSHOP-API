<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends ApiController
{
    public function __construct(protected PurchaseService $purchaseService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Purchase::with(['supplier', 'warehouse', 'user']);

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to . ' 23:59:59']);
        }

        $purchases = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($purchases);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id'          => 'required|exists:suppliers,id',
            'warehouse_id'         => 'required|exists:warehouses,id',
            'discount'             => 'nullable|numeric|min:0',
            'tax'                  => 'nullable|numeric|min:0',
            'paid_amount'          => 'nullable|numeric|min:0',
            'payment_method_id'    => 'nullable|exists:payment_methods,id',
            'note'                 => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        // Subtotal calculate
        $subtotal = collect($request->items)->sum(function ($item) {
            return $item['quantity'] * $item['purchase_price'];
        });

        $discount     = $request->discount ?? 0;
        $tax          = $request->tax ?? 0;
        $totalAmount  = $subtotal - $discount + $tax;

        $data = $request->all();
        $data['subtotal']     = $subtotal;
        $data['total_amount'] = $totalAmount;

        $purchase = $this->purchaseService->completePurchase($data, $request->user()->id);

        return $this->success($purchase, 'Purchase সফল হয়েছে', 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['supplier', 'warehouse', 'user', 'items.product', 'payments.paymentMethod']);
        return $this->success($purchase);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $request->validate([
            'note'   => 'nullable|string',
            'status' => 'nullable|in:draft,ordered,received,partial,completed,cancelled',
        ]);

        $purchase->update($request->only(['note', 'status']));
        return $this->success($purchase, 'Purchase আপডেট হয়েছে');
    }

    public function payment(Request $request, Purchase $purchase): JsonResponse
    {
        $request->validate([
            'amount'            => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'account_id'        => 'nullable|exists:accounts,id',
            'transaction_id'    => 'nullable|string',
            'note'              => 'nullable|string',
        ]);

        if ($request->amount > $purchase->due_amount) {
            return $this->error('Payment amount due amount এর চেয়ে বেশি হতে পারবে না', 400);
        }

        $purchase = $this->purchaseService->addPayment(
            $purchase,
            $request->all(),
            $request->user()->id
        );

        return $this->success($purchase, 'Payment সফল হয়েছে');
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $suppliers = $query->orderBy('name')->paginate($request->per_page ?? 20);
        return $this->paginated($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => 'required|string|max:20|unique:suppliers',
            'email'   => 'nullable|email',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:100',
            'note'    => 'nullable|string',
        ]);

        $supplier = Supplier::create($request->all());
        return $this->success($supplier, 'Supplier তৈরি হয়েছে', 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success($supplier);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => 'required|string|max:20|unique:suppliers,phone,' . $supplier->id,
            'email'   => 'nullable|email',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:100',
            'note'    => 'nullable|string',
        ]);

        $supplier->update($request->all());
        return $this->success($supplier, 'Supplier আপডেট হয়েছে');
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();
        return $this->success([], 'Supplier মুছে ফেলা হয়েছে');
    }

    public function ledger(Supplier $supplier): JsonResponse
    {
        $purchases = Purchase::with(['payments'])
            ->where('supplier_id', $supplier->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                return [
                    'invoice_no'     => $purchase->invoice_no,
                    'date'           => $purchase->created_at->format('Y-m-d'),
                    'total_amount'   => $purchase->total_amount,
                    'paid_amount'    => $purchase->paid_amount,
                    'due_amount'     => $purchase->due_amount,
                    'payment_status' => $purchase->payment_status,
                    'status'         => $purchase->status,
                ];
            });

        return $this->success([
            'supplier'   => $supplier,
            'purchases'  => $purchases,
            'total_due'  => $supplier->total_due,
        ]);
    }
}
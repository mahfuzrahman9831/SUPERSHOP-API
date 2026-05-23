<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\CreateSaleRequest;
use App\Http\Requests\Sale\CollectDueRequest;
use App\Models\Sale;
use App\Models\HeldSale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(protected SaleService $saleService) {}

    // GET /api/v1/sales
    public function index(Request $request): JsonResponse
    {
        $sales = Sale::with(['customer', 'user'])
            ->when($request->from,        fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,          fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status,      fn($q) => $q->where('status', $request->status))
            ->when($request->search,      fn($q) => $q->where('invoice_no', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->success($sales);
    }

    // POST /api/v1/sales
    public function store(CreateSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->saleService->createSale($request->validated());
            return $this->success($sale, 'Sale সফলভাবে তৈরি হয়েছে।', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // GET /api/v1/sales/{id}
    public function show(int $id): JsonResponse
    {
        $sale = Sale::with([
            'items.product',
            'items.variant',
            'items.layers',
            'payments.method',
            'customer',
            'user',
            'warehouse',
        ])->findOrFail($id);

        return $this->success($sale);
    }

    // PUT /api/v1/sales/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $sale = Sale::findOrFail($id);

        if ($sale->status !== 'draft') {
            return $this->error('শুধুমাত্র draft sale update করা যাবে।', 422);
        }

        $sale->update([
            'note' => $request->note ?? $sale->note,
        ]);

        return $this->success($sale, 'Sale update হয়েছে।');
    }

    // DELETE /api/v1/sales/{id}
    public function destroy(int $id): JsonResponse
    {
        $sale = Sale::findOrFail($id);

        if ($sale->status === 'completed') {
            return $this->error('Completed sale delete করা যাবে না। Cancel করুন।', 422);
        }

        $sale->delete();

        return $this->success(null, 'Sale মুছে ফেলা হয়েছে।');
    }

    // POST /api/v1/sales/{id}/payment
    public function collectDue(CollectDueRequest $request, int $id): JsonResponse
    {
        try {
            $sale = $this->saleService->collectDue($id, $request->payments);
            return $this->success($sale, 'Payment গ্রহণ করা হয়েছে।');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // POST /api/v1/sales/{id}/cancel
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $sale = $this->saleService->cancelSale($id, $request->reason ?? '');
            return $this->success($sale, 'Sale cancel করা হয়েছে।');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // GET /api/v1/sales/{id}/invoice
    public function invoice(int $id): JsonResponse
    {
        $sale = Sale::with([
            'items.product',
            'items.variant',
            'payments.method',
            'customer',
            'warehouse',
        ])->findOrFail($id);

        return $this->success($sale);
    }

    // GET /api/v1/sales/{id}/invoice/pdf
    public function invoicePdf(int $id)
    {
        $sale = Sale::with([
            'items.product',
            'payments.method',
            'customer',
            'warehouse',
        ])->findOrFail($id);

        $pdf = app('dompdf.wrapper')
            ->loadView('invoices.sale', compact('sale'));

        return $pdf->download("invoice-{$sale->invoice_no}.pdf");
    }
}
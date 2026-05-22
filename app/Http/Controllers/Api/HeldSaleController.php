<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeldSale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HeldSaleController extends Controller
{
    public function __construct(protected SaleService $saleService) {}

    // GET /api/held-sales
    public function index(): JsonResponse
    {
        $held = HeldSale::with('customer')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return $this->success($held);
    }

    // POST /api/held-sales
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cart'        => 'required|array|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'shift_id'    => 'nullable|exists:shifts,id',
            'note'        => 'nullable|string|max:200',
        ]);

        $held = $this->saleService->holdSale($request->all());
        return $this->success($held, 'Sale hold করা হয়েছে।', 201);
    }

    // GET /api/held-sales/{id}/resume
    public function resume(int $id): JsonResponse
    {
        try {
            $cart = $this->saleService->resumeHeldSale($id);
            return $this->success(['cart' => $cart], 'Cart restore করা হয়েছে।');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    // DELETE /api/held-sales/{id}
    public function destroy(int $id): JsonResponse
    {
        HeldSale::where('user_id', Auth::id())
            ->findOrFail($id)
            ->delete();

        return $this->success(null, 'Held sale মুছে ফেলা হয়েছে।');
    }
}
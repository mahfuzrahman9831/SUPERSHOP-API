<?php

namespace App\Http\Controllers\Api;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends ApiController
{
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::orderBy('name')->get();
        return $this->success($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'address'    => 'nullable|string',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        if ($request->is_default) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create($request->all());
        return $this->success($warehouse, 'Warehouse তৈরি হয়েছে', 201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load('stocks.product');
        return $this->success($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'address'    => 'nullable|string',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        if ($request->is_default) {
            Warehouse::where('is_default', true)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);
        }

        $warehouse->update($request->all());
        return $this->success($warehouse, 'Warehouse আপডেট হয়েছে');
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if ($warehouse->is_default) {
            return $this->error('Default Warehouse মুছে ফেলা যাবে না', 400);
        }

        $warehouse->delete();
        return $this->success([], 'Warehouse মুছে ফেলা হয়েছে');
    }

    public function stocks(Warehouse $warehouse): JsonResponse
    {
        $stocks = WarehouseStock::with(['product.unit', 'product.category'])
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->get();

        return $this->success($stocks);
    }
}
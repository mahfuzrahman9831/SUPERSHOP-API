<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductBundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBundleController extends ApiController
{
    public function index(): JsonResponse
    {
        $bundles = ProductBundle::with('items.product')->get();
        return $this->success($bundles);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'bundle_price' => 'required|numeric|min:0',
            'discount'     => 'nullable|numeric|min:0',
            'is_active'    => 'boolean',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:1',
        ]);

        $bundle = ProductBundle::create($request->except('items'));

        foreach ($request->items as $item) {
            $bundle->items()->create($item);
        }

        $bundle->load('items.product');
        return $this->success($bundle, 'Bundle তৈরি হয়েছে', 201);
    }

    public function show(ProductBundle $productBundle): JsonResponse
    {
        $productBundle->load('items.product');
        return $this->success($productBundle);
    }

    public function update(Request $request, ProductBundle $productBundle): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'bundle_price' => 'required|numeric|min:0',
            'discount'     => 'nullable|numeric|min:0',
            'is_active'    => 'boolean',
            'items'        => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:1',
        ]);

        $productBundle->update($request->except('items'));

        if ($request->has('items')) {
            $productBundle->items()->delete();
            foreach ($request->items as $item) {
                $productBundle->items()->create($item);
            }
        }

        $productBundle->load('items.product');
        return $this->success($productBundle, 'Bundle আপডেট হয়েছে');
    }

    public function destroy(ProductBundle $productBundle): JsonResponse
    {
        $productBundle->items()->delete();
        $productBundle->delete();
        return $this->success([], 'Bundle মুছে ফেলা হয়েছে');
    }
}
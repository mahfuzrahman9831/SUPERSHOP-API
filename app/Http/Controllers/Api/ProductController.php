<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['brand', 'category', 'unit', 'taxRate', 'images'])
            ->where('is_active', true);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('barcode', $request->search)
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->orderBy('name')->paginate($request->per_page ?? 20);
        return $this->paginated($products);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'category_id'           => 'nullable|exists:categories,id',
            'brand_id'              => 'nullable|exists:brands,id',
            'unit_id'               => 'nullable|exists:units,id',
            'tax_rate_id'           => 'nullable|exists:tax_rates,id',
            'barcode'               => 'nullable|string|unique:products',
            'sku'                   => 'nullable|string|unique:products',
            'description'           => 'nullable|string',
            'last_purchase_price'   => 'nullable|numeric|min:0',
            'default_selling_price' => 'required|numeric|min:0',
            'min_selling_price'     => 'nullable|numeric|min:0',
            'low_stock_alert'       => 'nullable|numeric|min:0',
            'costing_method'        => 'nullable|in:fifo,lifo,avg',
            'has_variants'          => 'boolean',
            'has_serial'            => 'boolean',
            'has_batch'             => 'boolean',
            'is_active'             => 'boolean',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name) . '-' . uniqid();

        $product = Product::create($data);
        $product->load(['brand', 'category', 'unit', 'taxRate']);

        return $this->success($product, 'Product তৈরি হয়েছে', 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['brand', 'category', 'unit', 'taxRate', 'images', 'variants', 'batches']);
        return $this->success($product);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'category_id'           => 'nullable|exists:categories,id',
            'brand_id'              => 'nullable|exists:brands,id',
            'unit_id'               => 'nullable|exists:units,id',
            'tax_rate_id'           => 'nullable|exists:tax_rates,id',
            'barcode'               => 'nullable|string|unique:products,barcode,' . $product->id,
            'sku'                   => 'nullable|string|unique:products,sku,' . $product->id,
            'description'           => 'nullable|string',
            'last_purchase_price'   => 'nullable|numeric|min:0',
            'default_selling_price' => 'required|numeric|min:0',
            'min_selling_price'     => 'nullable|numeric|min:0',
            'low_stock_alert'       => 'nullable|numeric|min:0',
            'costing_method'        => 'nullable|in:fifo,lifo,avg',
            'has_variants'          => 'boolean',
            'has_serial'            => 'boolean',
            'has_batch'             => 'boolean',
            'is_active'             => 'boolean',
        ]);

        $product->update($request->all());
        $product->load(['brand', 'category', 'unit', 'taxRate']);

        return $this->success($product, 'Product আপডেট হয়েছে');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return $this->success([], 'Product মুছে ফেলা হয়েছে');
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $products = Product::with(['unit', 'images'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('barcode', $query)
                    ->orWhere('sku', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();

        return $this->success($products);
    }

    public function lowStock(): JsonResponse
    {
        $products = Product::with(['category', 'unit'])
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->orderBy('stock_quantity')
            ->get();

        return $this->success($products);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends ApiController
{
    public function index(): JsonResponse
    {
        $brands = Brand::orderBy('name')->get();
        return $this->success($brands);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:brands',
            'image'       => 'nullable|string',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $brand = Brand::create($request->all());
        return $this->success($brand, 'Brand তৈরি হয়েছে', 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return $this->success($brand);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:brands,name,' . $brand->id,
            'image'       => 'nullable|string',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $brand->update($request->all());
        return $this->success($brand, 'Brand আপডেট হয়েছে');
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();
        return $this->success([], 'Brand মুছে ফেলা হয়েছে');
    }
}
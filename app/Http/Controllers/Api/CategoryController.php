<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
        return $this->success($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'parent_id'   => 'nullable|exists:categories,id',
            'image'       => 'nullable|string',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name) . '-' . uniqid();

        $category = Category::create($data);
        return $this->success($category, 'Category তৈরি হয়েছে', 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('children', 'parent');
        return $this->success($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'parent_id'   => 'nullable|exists:categories,id',
            'image'       => 'nullable|string',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name) . '-' . uniqid();

        $category->update($data);
        return $this->success($category, 'Category আপডেট হয়েছে');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return $this->success([], 'Category মুছে ফেলা হয়েছে');
    }
}
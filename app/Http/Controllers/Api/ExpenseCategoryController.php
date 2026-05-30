<?php

namespace App\Http\Controllers\Api;

use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return $this->success($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $category = ExpenseCategory::create($request->all());
        return $this->success($category, 'Expense Category তৈরি হয়েছে', 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        return $this->success($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $expenseCategory->update($request->all());
        return $this->success($expenseCategory, 'Expense Category আপডেট হয়েছে');
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $expenseCategory->delete();
        return $this->success([], 'Expense Category মুছে ফেলা হয়েছে');
    }
}
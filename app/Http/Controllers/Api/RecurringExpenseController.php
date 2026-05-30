<?php

namespace App\Http\Controllers\Api;

use App\Models\RecurringExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringExpenseController extends ApiController
{
    public function index(): JsonResponse
    {
        $recurring = RecurringExpense::with('category')
            ->orderBy('next_due_date')
            ->get();
        return $this->success($recurring);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'name'                => 'required|string|max:100',
            'amount'              => 'required|numeric|min:0.01',
            'frequency'           => 'required|in:daily,weekly,monthly,yearly',
            'next_due_date'       => 'required|date',
        ]);

        $recurring = RecurringExpense::create($request->all());
        return $this->success($recurring, 'Recurring Expense তৈরি হয়েছে', 201);
    }

    public function show(RecurringExpense $recurringExpense): JsonResponse
    {
        $recurringExpense->load('category');
        return $this->success($recurringExpense);
    }

    public function update(Request $request, RecurringExpense $recurringExpense): JsonResponse
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'amount'        => 'required|numeric|min:0.01',
            'frequency'     => 'required|in:daily,weekly,monthly,yearly',
            'next_due_date' => 'required|date',
            'is_active'     => 'boolean',
        ]);

        $recurringExpense->update($request->all());
        return $this->success($recurringExpense, 'Recurring Expense আপডেট হয়েছে');
    }

    public function destroy(RecurringExpense $recurringExpense): JsonResponse
    {
        $recurringExpense->delete();
        return $this->success([], 'Recurring Expense মুছে ফেলা হয়েছে');
    }
}
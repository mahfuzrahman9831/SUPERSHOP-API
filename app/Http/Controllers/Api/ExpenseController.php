<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['category', 'user', 'paymentMethod']);

        if ($request->category_id) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->from && $request->to) {
            $query->whereBetween('expense_date', [$request->from, $request->to]);
        }

        if ($request->is_approved !== null) {
            $query->where('is_approved', $request->is_approved);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'account_id'          => 'required|exists:accounts,id',
            'payment_method_id'   => 'required|exists:payment_methods,id',
            'amount'              => 'required|numeric|min:0.01',
            'expense_date'        => 'required|date',
            'note'                => 'nullable|string',
            'attachment'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::transaction(function () use ($request) {

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('expenses', 'public');
            }

            $expense = Expense::create([
                'expense_category_id' => $request->expense_category_id,
                'user_id'             => $request->user()->id,
                'account_id'          => $request->account_id,
                'payment_method_id'   => $request->payment_method_id,
                'amount'              => $request->amount,
                'attachment'          => $attachmentPath,
                'note'                => $request->note,
                'expense_date'        => $request->expense_date,
                'is_approved'         => false,
            ]);

            // Account balance কমাও
            $account = Account::findOrFail($request->account_id);
            $account->decrement('balance', $request->amount);

            // Account Transaction
            AccountTransaction::create([
                'account_id'       => $account->id,
                'type'             => 'debit',
                'amount'           => $request->amount,
                'balance_after'    => $account->fresh()->balance,
                'transaction_date' => $request->expense_date,
                'reference_type'   => Expense::class,
                'reference_id'     => $expense->id,
                'note'             => 'Expense - ' . $expense->id,
            ]);
        });

        return $this->success([], 'Expense তৈরি হয়েছে', 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['category', 'user', 'paymentMethod', 'account']);
        return $this->success($expense);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $request->validate([
            'note'        => 'nullable|string',
            'expense_date' => 'nullable|date',
        ]);

        $expense->update($request->only(['note', 'expense_date']));
        return $this->success($expense, 'Expense আপডেট হয়েছে');
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();
        return $this->success([], 'Expense মুছে ফেলা হয়েছে');
    }

    public function approve(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->is_approved) {
            return $this->error('এই Expense আগেই approve হয়েছে', 400);
        }

        $expense->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
        ]);

        return $this->success($expense, 'Expense approve হয়েছে');
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Shift;
use App\Models\ShiftTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends ApiController
{
    // Shift Open
    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'opening_cash' => 'required|numeric|min:0',
            'note'         => 'nullable|string',
        ]);

        // আগের open shift আছে কিনা check
        $existing = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $this->error('আপনার একটি shift ইতিমধ্যে চালু আছে', 400);
        }

        $shift = Shift::create([
            'user_id'      => $request->user()->id,
            'warehouse_id' => $request->warehouse_id,
            'opening_cash' => $request->opening_cash,
            'status'       => 'open',
            'note'         => $request->note,
            'opened_at'    => now(),
        ]);

        return $this->success($shift, 'Shift চালু হয়েছে', 201);
    }

    // Shift Close
    public function close(Request $request): JsonResponse
    {
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'note'         => 'nullable|string',
        ]);

        $shift = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return $this->error('কোনো open shift নেই', 400);
        }

        DB::transaction(function () use ($request, $shift) {

            // Shift এর total sales calculate
            $totalSales = Sale::where('shift_id', $shift->id)
                ->where('status', 'completed')
                ->sum('total_amount');

            // Shift এর total expense calculate
            $totalExpense = ShiftTransaction::where('shift_id', $shift->id)
                ->where('type', 'expense')
                ->sum('amount');

            // Expected cash
            $expectedCash = $shift->opening_cash + $totalSales - $totalExpense;
            $difference   = $request->closing_cash - $expectedCash;

            $shift->update([
                'closing_cash'  => $request->closing_cash,
                'expected_cash' => $expectedCash,
                'difference'    => $difference,
                'total_sales'   => $totalSales,
                'total_expense' => $totalExpense,
                'status'        => 'closed',
                'note'          => $request->note,
                'closed_at'     => now(),
            ]);
        });

        return $this->success($shift->fresh(), 'Shift বন্ধ হয়েছে');
    }

    // Current Shift
    public function current(Request $request): JsonResponse
    {
        $shift = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->with(['warehouse', 'user'])
            ->first();

        if (!$shift) {
            return $this->error('কোনো open shift নেই', 404);
        }

        return $this->success($shift);
    }

    // Shift Summary
    public function summary(Shift $shift): JsonResponse
    {
        $shift->load(['warehouse', 'user', 'transactions']);

        $sales = Sale::where('shift_id', $shift->id)
            ->where('status', 'completed')
            ->with('items')
            ->get();

        $totalSales   = $sales->sum('total_amount');
        $totalProfit  = $sales->sum('profit');
        $cashSales    = $sales->whereIn('payment_status', ['paid', 'partial'])->sum('paid_amount');

        return $this->success([
            'shift'         => $shift,
            'total_sales'   => $totalSales,
            'total_profit'  => $totalProfit,
            'cash_sales'    => $cashSales,
            'total_expense' => $shift->total_expense,
            'opening_cash'  => $shift->opening_cash,
            'expected_cash' => $shift->expected_cash,
            'closing_cash'  => $shift->closing_cash,
            'difference'    => $shift->difference,
            'sales_count'   => $sales->count(),
        ]);
    }

    // Cash In/Out
    public function cashInOut(Request $request): JsonResponse
    {
        $request->validate([
            'type'   => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'nullable|string',
        ]);

        $shift = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return $this->error('কোনো open shift নেই', 400);
        }

        ShiftTransaction::create([
            'shift_id' => $shift->id,
            'type'     => $request->type,
            'amount'   => $request->amount,
            'note'     => $request->note,
        ]);

        return $this->success([], $request->type === 'cash_in' ? 'Cash In সফল' : 'Cash Out সফল');
    }
}
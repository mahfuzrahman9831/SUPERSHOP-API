<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\LoyaltyPoint;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('group');

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        if ($request->is_vip) {
            $query->where('is_vip', true);
        }

        if ($request->is_blacklisted) {
            $query->where('is_blacklisted', true);
        }

        $customers = $query->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'              => 'required|string|max:100',
            'phone'             => 'required|string|max:20|unique:customers',
            'email'             => 'nullable|email',
            'address'           => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit'      => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($request->all());
        return $this->success($customer, 'Customer তৈরি হয়েছে', 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('group');
        return $this->success($customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'name'              => 'required|string|max:100',
            'phone'             => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email'             => 'nullable|email',
            'address'           => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit'      => 'nullable|numeric|min:0',
        ]);

        $customer->update($request->all());
        return $this->success($customer, 'Customer আপডেট হয়েছে');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return $this->success([], 'Customer মুছে ফেলা হয়েছে');
    }

    // Customer Ledger
    public function ledger(Customer $customer): JsonResponse
    {
        $sales = Sale::with(['payments'])
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'invoice_no'     => $sale->invoice_no,
                    'date'           => $sale->created_at->format('Y-m-d'),
                    'total_amount'   => $sale->total_amount,
                    'paid_amount'    => $sale->paid_amount,
                    'due_amount'     => $sale->due_amount,
                    'payment_status' => $sale->payment_status,
                    'status'         => $sale->status,
                ];
            });

        return $this->success([
            'customer'      => $customer->load('group'),
            'sales'         => $sales,
            'total_due'     => $customer->total_due,
            'loyalty_points' => $customer->loyalty_points,
        ]);
    }

    // Due Payment
    public function payment(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'amount'            => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'account_id'        => 'nullable|exists:accounts,id',
            'note'              => 'nullable|string',
        ]);

        if ($request->amount > $customer->total_due) {
            return $this->error('Payment amount due amount এর চেয়ে বেশি হতে পারবে না', 400);
        }

        DB::transaction(function () use ($request, $customer) {

            $account = Account::findOrFail(
                $request->account_id ?? Account::where('is_default', true)->first()->id
            );

            // Payment record
            Payment::create([
                'payable_type'      => Customer::class,
                'payable_id'        => $customer->id,
                'payment_method_id' => $request->payment_method_id,
                'account_id'        => $account->id,
                'type'              => 'received',
                'amount'            => $request->amount,
                'note'              => $request->note,
                'paid_at'           => now(),
                'created_by'        => $request->user()->id,
            ]);

            // Customer total_due কমাও
            $customer->decrement('total_due', $request->amount);

            // Account balance বাড়াও
            $account->increment('balance', $request->amount);

            // Account Transaction
            AccountTransaction::create([
                'account_id'       => $account->id,
                'type'             => 'credit',
                'amount'           => $request->amount,
                'balance_after'    => $account->fresh()->balance,
                'transaction_date' => now()->toDateString(),
                'reference_type'   => Customer::class,
                'reference_id'     => $customer->id,
                'note'             => 'Due Collection - ' . $customer->name,
            ]);

            // Sale due update (oldest due first)
            $remaining = $request->amount;
            $dueSales  = Sale::where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($dueSales as $sale) {
                if ($remaining <= 0) break;

                $pay = min($sale->due_amount, $remaining);
                $sale->update([
                    'paid_amount'    => $sale->paid_amount + $pay,
                    'due_amount'     => $sale->due_amount - $pay,
                    'payment_status' => ($sale->due_amount - $pay) <= 0 ? 'paid' : 'partial',
                ]);

                $remaining -= $pay;
            }
        });

        return $this->success(
            $customer->fresh(),
            'Due Collection সফল হয়েছে'
        );
    }

    // Loyalty Points History
    public function loyalty(Customer $customer): JsonResponse
    {
        $points = LoyaltyPoint::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success([
            'total_points' => $customer->loyalty_points,
            'history'      => $points,
        ]);
    }

    // Loyalty Points Redeem
    public function redeemLoyalty(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'points' => 'required|numeric|min:1',
        ]);

        if ($request->points > $customer->loyalty_points) {
            return $this->error('পর্যাপ্ত loyalty points নেই', 400);
        }

        DB::transaction(function () use ($request, $customer) {
            LoyaltyPoint::create([
                'customer_id' => $customer->id,
                'type'        => 'redeem',
                'points'      => $request->points,
                'note'        => 'Points Redeemed',
            ]);

            $customer->decrement('loyalty_points', $request->points);
        });

        return $this->success(
            $customer->fresh(),
            $request->points . ' Points redeem হয়েছে'
        );
    }

    // VIP Toggle
    public function toggleVip(Customer $customer): JsonResponse
    {
        $customer->update(['is_vip' => !$customer->is_vip]);
        $msg = $customer->is_vip ? 'VIP করা হয়েছে' : 'VIP বাতিল করা হয়েছে';
        return $this->success($customer, $msg);
    }

    // Blacklist Toggle
    public function toggleBlacklist(Customer $customer): JsonResponse
    {
        $customer->update(['is_blacklisted' => !$customer->is_blacklisted]);
        $msg = $customer->is_blacklisted ? 'Blacklist করা হয়েছে' : 'Blacklist থেকে সরানো হয়েছে';
        return $this->success($customer, $msg);
    }
}
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Account;
use App\Models\AccountTransaction;

class PaymentService
{
    public function create(array $data): Payment
    {
        $payment = Payment::create([
            'payable_type'      => $data['payable_type'],
            'payable_id'        => $data['payable_id'],
            'payment_method_id' => $data['payment_method_id'],
            'amount'            => $data['amount'],
            'reference'         => $data['reference'] ?? null,
            'paid_at'           => $data['paid_at'],
            'created_by'        => $data['created_by'],
        ]);

        // Account Balance Update (যদি account_id দেওয়া থাকে)
        if (!empty($data['account_id'])) {
            $direction = $data['direction'] ?? 'in'; // 'in' = জমা, 'out' = খরচ

            Account::where('id', $data['account_id'])
                ->when($direction === 'in',  fn($q) => $q->increment('balance', $data['amount']))
                ->when($direction === 'out', fn($q) => $q->decrement('balance', $data['amount']));

            AccountTransaction::create([
                'account_id'     => $data['account_id'],
                'payment_id'     => $payment->id,
                'type'           => $direction,
                'amount'         => $data['amount'],
                'balance_after'  => Account::find($data['account_id'])->balance,
                'reference_type' => $data['payable_type'],
                'reference_id'   => $data['payable_id'],
                'created_by'     => $data['created_by'],
            ]);
        }

        return $payment;
    }
}
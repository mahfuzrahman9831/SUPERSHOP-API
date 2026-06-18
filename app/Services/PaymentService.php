<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;

class PaymentService
{
    public function create(array $data): Payment
    {
        return Payment::create([
            'payable_type'      => $data['payable_type'],
            'payable_id'        => $data['payable_id'],
            'payment_method_id' => $data['payment_method_id'],
            'account_id'        => $data['account_id'] ?? 1,  // default main cash account
            'type'              => $data['type'] ?? 'received',
            'amount'            => $data['amount'],
            'transaction_id'    => $data['reference'] ?? null,
            'note'              => $data['note'] ?? null,
            'paid_at'           => $data['paid_at'] ?? Carbon::now(),
            'created_by'        => $data['created_by'],
        ]);
    }
}
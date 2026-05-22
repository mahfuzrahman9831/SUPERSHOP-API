<?php

namespace App\Services;

use App\Models\InvoiceCounter;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generate(string $type): string
    {
        return DB::transaction(function () use ($type) {
            $counter = InvoiceCounter::where('type', $type)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $counter->last_number + 1;

            $counter->update(['last_number' => $number]);

            return $counter->prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        });
    }
}
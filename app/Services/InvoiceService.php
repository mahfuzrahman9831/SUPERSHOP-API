<?php

namespace App\Services;

use App\Models\InvoiceCounter;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generate(string $module): string
    {
        return DB::transaction(function () use ($module) {
            $counter = InvoiceCounter::where('module', $module)  // 'type' → 'module'
                ->lockForUpdate()
                ->firstOrFail();

            $number = $counter->current_number + 1;             // 'last_number' → 'current_number'

            $counter->update(['current_number' => $number]);    // 'last_number' → 'current_number'

            return $counter->prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        });
    }
}
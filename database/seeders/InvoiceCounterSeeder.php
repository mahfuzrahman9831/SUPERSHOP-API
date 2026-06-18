<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceCounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            ['module' => 'sale',      'prefix' => 'SALE', 'current_number' => 0],
            ['module' => 'purchase',  'prefix' => 'PUR',  'current_number' => 0],
            ['module' => 'return',    'prefix' => 'RET',  'current_number' => 0],
            ['module' => 'quotation', 'prefix' => 'QUO',  'current_number' => 0],
            ['module' => 'expense',   'prefix' => 'EXP',  'current_number' => 0],
        ];

        foreach ($counters as $counter) {
            DB::table('invoice_counters')->insertOrIgnore($counter);
        }
    }
}
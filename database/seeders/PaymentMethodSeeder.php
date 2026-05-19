<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Cash',          'type' => 'cash',           'is_active' => true],
            ['name' => 'bKash',         'type' => 'mobile_banking', 'is_active' => true],
            ['name' => 'Nagad',         'type' => 'mobile_banking', 'is_active' => true],
            ['name' => 'Card',          'type' => 'card',           'is_active' => true],
            ['name' => 'Bank Transfer', 'type' => 'bank',           'is_active' => true],
            ['name' => 'Store Credit',  'type' => 'store_credit',   'is_active' => true],
        ];

        foreach ($methods as $method) {
            DB::table('payment_methods')->insertOrIgnore($method);
        }
    }
}
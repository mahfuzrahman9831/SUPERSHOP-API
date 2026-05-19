<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'purchase',        'direction' => 'in'],
            ['name' => 'sale',            'direction' => 'out'],
            ['name' => 'sale_return',     'direction' => 'in'],
            ['name' => 'purchase_return', 'direction' => 'out'],
            ['name' => 'damage',          'direction' => 'out'],
            ['name' => 'adjustment',      'direction' => 'in'],
            ['name' => 'opening_stock',   'direction' => 'in'],
            ['name' => 'transfer_in',     'direction' => 'in'],
            ['name' => 'transfer_out',    'direction' => 'out'],
            ['name' => 'expired',         'direction' => 'out'],
            ['name' => 'wastage',         'direction' => 'out'],
            ['name' => 'bundle_break',    'direction' => 'in'],
        ];

        foreach ($types as $type) {
            DB::table('stock_movement_types')->insertOrIgnore($type);
        }
    }
}
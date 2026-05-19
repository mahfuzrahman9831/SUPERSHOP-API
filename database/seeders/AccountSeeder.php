<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->insertOrIgnore([
            [
                'name'           => 'Main Cash',
                'type'           => 'cash',
                'account_number' => null,
                'balance'        => 0,
                'is_default'     => true,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]
        ]);
    }
}
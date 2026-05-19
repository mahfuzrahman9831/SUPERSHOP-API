<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            PaymentMethodSeeder::class,
            WarehouseSeeder::class,
            StockMovementTypeSeeder::class,
            SettingSeeder::class,
            InvoiceCounterSeeder::class,
            AccountSeeder::class,
        ]);
    }
}
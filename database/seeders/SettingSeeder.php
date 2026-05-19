<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'shop_name',               'value' => 'My SuperShop'],
            ['key' => 'shop_address',            'value' => ''],
            ['key' => 'shop_phone',              'value' => ''],
            ['key' => 'shop_email',              'value' => ''],
            ['key' => 'shop_logo',               'value' => ''],
            ['key' => 'shop_currency',           'value' => 'BDT'],
            ['key' => 'shop_currency_symbol',    'value' => '৳'],
            ['key' => 'receipt_header',          'value' => 'Welcome to My SuperShop'],
            ['key' => 'receipt_footer',          'value' => 'Thank you for shopping with us!'],
            ['key' => 'low_stock_threshold',     'value' => '10'],
            ['key' => 'default_costing_method',  'value' => 'fifo'],
            ['key' => 'tax_rate',                'value' => '0'],
            ['key' => 'loyalty_points_rate',     'value' => '1'],
            ['key' => 'loyalty_redeem_rate',     'value' => '1'],
            ['key' => 'auto_backup_schedule',    'value' => 'daily'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }
}
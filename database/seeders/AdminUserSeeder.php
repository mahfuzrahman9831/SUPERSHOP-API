<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@supershop.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('Admin@123'),
                'is_active' => true,
            ]
        );

        $role = Role::findByName('Admin', 'sanctum');
        $user->assignRole($role);
    }
}
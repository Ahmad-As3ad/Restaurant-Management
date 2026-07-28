<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'System',
            'phone' => '0912345678',
            'address' => 'Damascus',
            'email' => 'admin@restaurant.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $admin->id,
            'balance' => 0,
            'total_deposited' => 0,
            'total_spent' => 0,
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@restaurant.com');
        $this->command->info('Password: password');
    }
}

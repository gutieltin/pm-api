<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
        'name' => 'System Admin',
        'email' => 'admin@company.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'must_reset_password' => false,
        ]);
    }
}

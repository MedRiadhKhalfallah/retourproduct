<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'riadh@example.com'],
            ['name' => 'Riadh', 'password' => Hash::make('RiadhRiadhRiadh123')],
        );

        User::updateOrCreate(
            ['email' => 'ahmed@example.com'],
            ['name' => 'Ahmed', 'password' => Hash::make('Ahmed123!')],
        );
    }
}

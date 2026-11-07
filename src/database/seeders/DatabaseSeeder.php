<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Idempotent : les comptes existants (et leurs mots de passe) ne sont jamais modifiés. */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'riadh@example.com'],
            ['name' => 'Riadh', 'password' => Hash::make('RiadhRiadhRiadh123')],
        );

        User::firstOrCreate(
            ['email' => 'ahmed@example.com'],
            ['name' => 'Ahmed', 'password' => Hash::make('Ahmed123!')],
        );
    }
}

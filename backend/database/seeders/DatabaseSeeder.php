<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BadgeSeeder::class);

        // Seed a default admin user for local development only
        if (app()->isLocal()) {
            User::firstOrCreate(
                ['email' => 'admin@smartadama.com'],
                [
                    'id'       => (string) Str::uuid(),
                    'name'     => 'Admin User',
                    'password' => Hash::make('password'),
                    'role'     => 'admin',
                ]
            );

            User::firstOrCreate(
                ['email' => 'learner@smartadama.com'],
                [
                    'id'       => (string) Str::uuid(),
                    'name'     => 'Test Learner',
                    'password' => Hash::make('password'),
                    'role'     => 'learner',
                ]
            );
        }
    }
}

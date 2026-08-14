<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@smartadama.com')->first();
        
        if ($user) {
            $user->password = Hash::make('password');
            $user->save();
            $this->command->info('Admin password reset to: password');
        } else {
            $this->command->info('Admin user not found');
        }
    }
}

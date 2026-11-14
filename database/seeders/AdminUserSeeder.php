<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@lostdocug.example'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@lostdocug.example',
                'phone' => '+256700000000',
                'password' => Hash::make('Admin@2026!Secure'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'reviewer@lostdocug.example'],
            [
                'name' => 'Document Reviewer',
                'email' => 'reviewer@lostdocug.example',
                'phone' => '+256700000001',
                'password' => Hash::make('Reviewer@2026!Secure'),
                'role' => 'reviewer',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );
    }
}

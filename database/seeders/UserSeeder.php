<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'aff_id' => Str::uuid7(),
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'password' => Hash::make('password'),
                'country' => 'USA',
                'image' => null,
                'otp' => null,
                'email_verified_at' => now(),
            ],
            [
                'aff_id' => Str::uuid7(),
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+2987654321',
                'password' => Hash::make('password'),
                'country' => 'Ghana',
                'image' => null,
                'otp' => null,
                'email_verified_at' => now(),
            ],
            [
                'aff_id' => Str::uuid7(),
                'name' => 'Partner User',
                'email' => 'partner@example.com',
                'phone' => '+1122334455',
                'password' => Hash::make('password'),
                'country' => 'UK',
                'image' => null,
                'otp' => null,
                'email_verified_at' => now(),
            ],
            [
                'aff_id' => Str::uuid7(),
                'name' => 'Ultimate User',
                'email' => 'ultimate@example.com',
                'phone' => '+9988776655',
                'password' => Hash::make('password'),
                'country' => 'Kenya',
                'image' => null,
                'otp' => null,
                'email_verified_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}

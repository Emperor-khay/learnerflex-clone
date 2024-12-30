<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
            WithdrawalSeeder::class,
            SaleSeeder::class,
            TransactionSeeder::class,
            VendorStatusSeeder::class,
            
            // Add other seeders here...
        ]);
    }
}

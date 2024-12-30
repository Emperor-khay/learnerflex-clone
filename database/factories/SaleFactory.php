<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition()
    {
        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => Product::factory(),
            'vendor_id' => User::factory(),
            'affiliate_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => 'success',
            'commission' => $this->faker->randomFloat(2, 1, 100),
            'currency' => $this->faker->currencyCode,
            'email' => $this->faker->safeEmail,
            'org_vendor' => $this->faker->randomFloat(2, 10, 500),
            'org_aff' => $this->faker->randomFloat(2, 1, 100),
            'org_company' => $this->faker->randomFloat(2, 1, 50),
        ];
    }
}
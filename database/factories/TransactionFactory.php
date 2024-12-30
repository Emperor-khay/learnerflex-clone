<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'tx_ref' => $this->faker->uuid,
            'user_id' => User::factory(),
            'affiliate_id' => User::factory(),
            'product_id' => Product::factory(),
            'vendor_id' => User::factory(),
            'email' => $this->faker->safeEmail,
            'transaction_id' => $this->faker->uuid,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => $this->faker->currencyCode,
            'status' => $this->faker->randomElement(['success', 'failed', 'pending']),
            'is_onboarded' => $this->faker->boolean,
            'description' => $this->faker->sentence,
            'org_company' => $this->faker->randomFloat(2, 1, 50),
            'org_vendor' => $this->faker->randomFloat(2, 10, 500),
            'org_aff' => $this->faker->randomFloat(2, 1, 100),
            'meta' => json_encode(['key' => 'value']),
        ];
    }
}

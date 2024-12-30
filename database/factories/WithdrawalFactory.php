<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(), // Creates a related user
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'bank_account' => $this->faker->bankAccountNumber,
            'bank_name' => $this->faker->company,
            'email' => $this->faker->safeEmail,
            'old_balance' => $this->faker->randomFloat(2, 1000, 10000),
            'status' => 'pending',
            'type' => $this->faker->randomElement(['vendor', 'affiliate']),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // Default password
            'aff_id' => Str::random(8),
            'role' => $this->faker->randomElement(['admin', 'vendor', 'affiliate']),
            'phone' => $this->faker->phoneNumber,
            'country' => $this->faker->country,
            'currency' => $this->faker->currencyCode,
            'market_access' => $this->faker->boolean,
        ];
    }
}

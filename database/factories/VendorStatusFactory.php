<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VendorStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorStatus>
 */
class VendorStatusFactory extends Factory
{
    protected $model = VendorStatus::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'sale_url' => $this->faker->url,
            'description' => $this->faker->paragraph,
            'review' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}

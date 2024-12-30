<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company,
            'photo' => $this->faker->imageUrl(),
            'description' => $this->faker->sentence(20),
            'x_link' => $this->faker->url,
            'ig_link' => $this->faker->url,
            'yt_link' => $this->faker->url,
            'fb_link' => $this->faker->url,
            'tt_link' => $this->faker->url,
            'display' => $this->faker->boolean,
        ];
    }
}

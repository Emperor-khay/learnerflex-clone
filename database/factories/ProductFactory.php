<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'vendor_id' => \App\Models\Vendor::factory(),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'image' => $this->faker->imageUrl(),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'old_price' => $this->faker->randomFloat(2, 20, 120),
            'file' => $this->faker->url(),
            'type' => $this->faker->randomElement(['digital', 'physical']),
            'commission' => $this->faker->randomFloat(2, 0, 50),
            'contact_email' => $this->faker->email(),
            'access_link' => $this->faker->url(),
            'vsl_pa_link' => $this->faker->url(),
            'sale_page_link' => $this->faker->url(),
            'sale_challenge_link' => $this->faker->url(),
            'promotional_material' => $this->faker->sentence(),
            'is_partnership' => $this->faker->boolean(),
            'is_affiliated' => $this->faker->boolean(),
            'x_link' => $this->faker->url(),
            'ig_link' => $this->faker->url(),
            'yt_link' => $this->faker->url(),
            'fb_link' => $this->faker->url(),
            'tt_link' => $this->faker->url(),
            'status' => 'active',
            'images' => json_encode([$this->faker->imageUrl(), $this->faker->imageUrl()]),
        ];
    }
}

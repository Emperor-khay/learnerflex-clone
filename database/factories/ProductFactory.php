<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(), // assuming you have a User model
            'vendor_id' => $this->faker->randomFloat(1, 10, 50), // assuming you have a Vendor model
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'image' => $this->faker->imageUrl(640, 480, 'products', true), // generates a random image URL
            'price' => $this->faker->randomFloat(2, 10, 1000), // random price between 10 and 1000
            'old_price' => $this->faker->randomFloat(2, 10, 1000), // optional old price
            'type' => $this->faker->randomElement(['digital', 'physical']), // product type
            'commission' => $this->faker->randomFloat(2, 1, 100), // random commission percentage
            'contact_email' => $this->faker->safeEmail,
            'access_link' => $this->faker->url,
            'vsl_pa_link' => $this->faker->url,
            'sale_page_link' => $this->faker->url,
            'sale_challenge_link' => $this->faker->url,
            'promotional_material' => $this->faker->text(50), // random promotional material text
            'is_partnership' => $this->faker->boolean,
            'is_affiliated' => $this->faker->boolean,
            'x_link' => $this->faker->url, // random social media links
            'ig_link' => $this->faker->url,
            'yt_link' => $this->faker->url,
            'fb_link' => $this->faker->url,
            'tt_link' => $this->faker->url,
            'status' => $this->faker->randomElement(['approved', 'pending']), // product status
        ];
    }
}

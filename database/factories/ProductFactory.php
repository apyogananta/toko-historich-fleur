<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_name' => $this->faker->word,
            'category_id' => Category::factory(),
            'original_price' => $this->faker->numberBetween(10000, 100000),
            'sale_price' => $this->faker->optional()->numberBetween(5000, 90000),
            'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
            'stock' => $this->faker->numberBetween(10, 100),
            'description' => $this->faker->sentence,
        ];
    }
}

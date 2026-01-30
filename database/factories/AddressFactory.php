<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_name' => $this->faker->name,
            'address_line1' => $this->faker->streetAddress,
            'address_line2' => "pagar seng",
            'province' => $this->faker->state,
            'city' => $this->faker->city,
            'postal_code' => $this->faker->postcode,
        ];
    }
}

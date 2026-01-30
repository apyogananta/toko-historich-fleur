<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SiteUser;
use App\Models\Address;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteUserAddress>
 */
class SiteUserAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_user_id' => SiteUser::factory(),
            'address_id' => Address::factory(),
            'is_default' => $this->faker->boolean,
        ];
    }
}

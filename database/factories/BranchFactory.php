<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Main Branch', 'North Branch', 'Cebu Branch', 'Iloilo Branch']),
            'code' => strtoupper(fake()->unique()->lexify('BR-???')),
            'status' => 'active',
            'address_street' => fake()->streetAddress(),
            'address_barangay' => fake()->randomElement(['San Lorenzo', 'Barangka', 'Mabini', 'Poblacion']),
            'address_city' => fake()->randomElement(['Makati City', 'Quezon City', 'Cebu City', 'Iloilo City']),
            'address_province' => fake()->randomElement(['Metro Manila', 'Cebu', 'Iloilo']),
            'address_region' => fake()->randomElement(['NCR', 'Region VII', 'Region VI']),
            'address_postal_code' => (string) fake()->numberBetween(1000, 9999),
            'address_country' => 'PH',
            'email' => fake()->unique()->companyEmail(),
            'phone' => '+63 917 '.fake()->unique()->numerify('### ####'),
            'latitude' => fake()->latitude(4, 21),
            'longitude' => fake()->longitude(116, 127),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => ['is_primary' => true]);
    }
}

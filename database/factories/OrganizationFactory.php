<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'status' => 'active',
            'business_name' => $name.' Sports Inc.',
            'tin' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'email' => fake()->unique()->companyEmail(),
            'phone' => '+63 917 '.fake()->unique()->numerify('### ####'),
            'address_street' => fake()->streetAddress(),
            'address_barangay' => fake()->randomElement(['San Lorenzo', 'Barangka', 'Mabini', 'Poblacion']),
            'address_city' => fake()->randomElement(['Makati City', 'Quezon City', 'Cebu City', 'Iloilo City']),
            'address_province' => fake()->randomElement(['Metro Manila', 'Cebu', 'Iloilo']),
            'address_region' => fake()->randomElement(['NCR', 'Region VII', 'Region VI']),
            'address_postal_code' => (string) fake()->numberBetween(1000, 9999),
            'address_country' => 'PH',
            'timezone' => 'Asia/Manila',
            'currency' => 'PHP',
            'tax_rate' => 12.00,
            'tax_inclusive' => true,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => 'suspended']);
    }
}

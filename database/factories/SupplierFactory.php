<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company().' Sports Supply',
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'mobile' => '09'.fake()->numerify('#########'),
            'is_active' => true,
        ];
    }
}

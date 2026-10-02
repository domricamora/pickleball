<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_id' => Branch::factory(),
            'employee_number' => strtoupper(fake()->unique()->bothify('EMP-###')),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '09'.fake()->numerify('#########'),
            'hired_on' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'role' => fake()->randomElement([
                Role::FRONT_DESK->value,
                Role::CASHIER->value,
                Role::STAFF->value,
                Role::COACH->value,
            ]),
            'commission_rate' => '0.00',
            'base_salary' => '0.00',
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'category' => fake()->randomElement([
                'rent', 'utilities', 'maintenance', 'supplies', 'staff', 'marketing',
            ]),
            'description' => fake()->sentence(3),
            // Set explicitly: an omitted value becomes NULL in PHP and would
            // bypass the column default.
            'amount' => fake()->randomElement(['500.00', '2500.00', '8000.00']),
            'currency' => 'PHP',
            'incurred_on' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
        ];
    }
}

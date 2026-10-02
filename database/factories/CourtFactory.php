<?php

namespace Database\Factories;

use App\Enums\CourtStatus;
use App\Enums\CourtSurface;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    protected $model = Court::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Court A', 'Court B', 'Court C', 'Championship Court']),
            'number' => (string) fake()->unique()->numberBetween(1, 99),
            'surface' => fake()->randomElement(CourtSurface::values()),
            'type' => fake()->randomElement(['standard', 'dedicated', 'tournament']),
            'setting' => fake()->randomElement(['indoor', 'outdoor']),
            'status' => CourtStatus::AVAILABLE->value,
            'capacity' => 4,
            'amenities' => fake()->randomElements(
                ['lighting', 'nets', 'ball machine', 'changing room', 'shade', 'water'],
                fake()->numberBetween(1, 3),
            ),
            'notes' => null,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (): array => ['status' => CourtStatus::MAINTENANCE->value]);
    }

    public function blocked(): static
    {
        return $this->state(fn (): array => ['status' => CourtStatus::BLOCKED->value]);
    }
}

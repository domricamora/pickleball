<?php

namespace Database\Factories;

use App\Enums\PriceType;
use App\Models\Court;
use App\Models\CourtPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtPrice>
 */
class CourtPriceFactory extends Factory
{
    protected $model = CourtPrice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Court::factory(),
            'court_id' => null,
            'branch_id' => null,
            'type' => fake()->randomElement(PriceType::values()),
            'amount' => fake()->randomFloat(2, 150, 800),
            'min_minutes' => 60,
            'increment_minutes' => 60,
            'starts_at_hour' => null,
            'ends_at_hour' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}

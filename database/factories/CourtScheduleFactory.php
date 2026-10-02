<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\CourtSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtSchedule>
 */
class CourtScheduleFactory extends Factory
{
    protected $model = CourtSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Court::factory(),
            'court_id' => null,
            'weekday' => fake()->numberBetween(0, 6),
            'opens_at' => '06:00:00',
            'closes_at' => '22:00:00',
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['is_closed' => true]);
    }
}

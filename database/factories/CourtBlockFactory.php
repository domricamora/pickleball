<?php

namespace Database\Factories;

use App\Enums\BlockType;
use App\Models\Court;
use App\Models\CourtBlock;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CourtBlock>
 */
class CourtBlockFactory extends Factory
{
    protected $model = CourtBlock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 week', '+1 month');

        return [
            'organization_id' => Court::factory(),
            'court_id' => null,
            'branch_id' => null,
            'type' => fake()->randomElement(BlockType::values()),
            'reason' => fake()->randomElement([
                'Net replacement',
                'Surface resurfacing',
                'Christmas Day',
                'Tournament final',
                'Private event',
            ]),
            'starts_on' => Carbon::instance($startsOn)->startOfDay(),
            'ends_on' => Carbon::instance($startsOn)->addDays(fake()->numberBetween(0, 3))->endOfDay(),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn (): array => ['starts_at' => null, 'ends_at' => null]);
    }
}

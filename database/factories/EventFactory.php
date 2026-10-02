<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Branch;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('+1 week', '+6 weeks');

        return [
            'organization_id' => Organization::factory(),
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement([
                'Saturday Open Play',
                'Beginner Clinic',
                'Corporate Cup',
                'Evening League Finals',
            ]),
            'type' => EventType::OPEN_PLAY->value,
            'status' => EventStatus::OPEN->value,
            'starts_at' => $starts,
            'ends_at' => (clone $starts)->modify('+3 hours'),
            'capacity' => 16,
            'fee' => '350.00',
            'currency' => 'PHP',
            'format' => 'doubles',
        ];
    }

    public function tournament(): static
    {
        return $this->state(fn (): array => ['type' => EventType::TOURNAMENT->value]);
    }
}

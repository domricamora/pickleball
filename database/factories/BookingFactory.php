<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 hour', '+3 days');
        $startsAt = Carbon::instance($startsAt)->setTime(18, 0);

        return [
            'organization_id' => Court::factory(),
            'branch_id' => fn (array $attributes) => Court::find($attributes['court_id'])?->branch_id,
            'court_id' => null,
            'user_id' => null,
            'customer_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'duration_minutes' => 60,
            'status' => BookingStatus::CONFIRMED->value,
            'amount' => '350.00',
            'currency' => 'PHP',
            'players' => 4,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => BookingStatus::PENDING->value]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => BookingStatus::CANCELLED->value,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * A booking occupying a given Manila-local time window.
     */
    public function between(string $start, string $end): static
    {
        return $this->state(fn (): array => [
            'starts_at' => $start,
            'ends_at' => $end,
            'duration_minutes' => (int) \Carbon\Carbon::parse($start)
                ->diffInMinutes(\Carbon\Carbon::parse($end)),
        ]);
    }
}

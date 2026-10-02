<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'booking_id' => null,
            'user_id' => null,
            'method' => PaymentMethod::GCASH->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => '400.00',
            'refunded_amount' => '0.00',
            'currency' => 'PHP',
        ];
    }

    public function forBooking(Booking $booking, string $amount = '400.00'): static
    {
        return $this->state(fn (): array => [
            'organization_id' => $booking->organization_id,
            'branch_id' => $booking->branch_id,
            'booking_id' => $booking->id,
            'amount' => $amount,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::PAID->value,
            'paid_at' => now(),
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (): array => ['method' => PaymentMethod::CASH->value]);
    }
}

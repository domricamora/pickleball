<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipSubscription>
 */
class MembershipSubscriptionFactory extends Factory
{
    protected $model = MembershipSubscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_plan_id' => MembershipPlan::factory(),
            'customer_id' => Customer::factory(),
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_on' => now()->subDays(5)->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'credits_granted' => 10,
            'credits_remaining' => 10,
            'is_unlimited' => false,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (): array => [
            'is_unlimited' => true,
            'credits_granted' => 0,
            'credits_remaining' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::EXPIRED->value,
            'starts_on' => now()->subMonths(3)->toDateString(),
            'ends_on' => now()->subMonth()->toDateString(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Models\MembershipPlan;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Play Regular', 'Play Often', 'Unlimited']),
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'price' => '2500.00',
            'currency' => 'PHP',
            'sessions_included' => 8,
            'is_active' => true,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Unlimited',
            'sessions_included' => null,
        ]);
    }
}

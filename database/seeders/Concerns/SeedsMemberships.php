<?php

namespace Database\Seeders\Concerns;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use Illuminate\Support\Collection;

/**
 * Membership plans in pesos and the subscriptions that pay for them.
 *
 * Membership revenue is one of the three sources the reporting service sums
 * (plan.md §20), so without subscriptions the revenue split shows two of
 * three columns empty on every dashboard.
 */
trait SeedsMemberships
{
    /**
     * @return Collection<int, MembershipPlan>
     */
    private function seedMembershipPlans(int $orgId, int $branchId): Collection
    {
        $plans = [
            [
                'name' => 'Play Regular',
                'description' => 'Eight court hours a month for the weekend player.',
                'billing_cycle' => BillingCycle::MONTHLY,
                'price' => 2500.00,
                'sessions_included' => 8,
                'priority_booking' => false,
                'member_only_events' => false,
                'guest_passes' => 1,
                'discount_percent' => 5,
            ],
            [
                'name' => 'Play Often',
                'description' => 'Sixteen hours a month, priority booking and two guest passes.',
                'billing_cycle' => BillingCycle::MONTHLY,
                'price' => 4200.00,
                'sessions_included' => 16,
                'priority_booking' => true,
                'member_only_events' => true,
                'guest_passes' => 2,
                'discount_percent' => 10,
            ],
            [
                'name' => 'Unlimited',
                'description' => 'Unlimited court access, first pick of the peak slots.',
                'billing_cycle' => BillingCycle::MONTHLY,
                'price' => 7500.00,
                'sessions_included' => null,
                'priority_booking' => true,
                'member_only_events' => true,
                'guest_passes' => 4,
                'discount_percent' => 15,
            ],
        ];

        $created = [];

        foreach ($plans as $plan) {
            $created[] = MembershipPlan::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'name' => $plan['name'],
                'description' => $plan['description'],
                'billing_cycle' => $plan['billing_cycle']->value,
                'price' => number_format($plan['price'], 2, '.', ''),
                'currency' => 'PHP',
                'sessions_included' => $plan['sessions_included'],
                'priority_booking' => $plan['priority_booking'],
                'member_only_events' => $plan['member_only_events'],
                'guest_passes' => $plan['guest_passes'],
                'discount_percent' => $plan['discount_percent'],
                'benefits' => ['Priority court booking', 'Member tournament entry', 'Pro shop discount'],
                'is_active' => true,
            ]);
        }

        return collect($created);
    }

    /**
     * @param  array<int, int>  $planIds
     * @param  array<int, int>  $customerIds
     */
    private function seedSubscriptions(int $orgId, array $planIds, array $customerIds): void
    {
        if ($planIds === [] || $customerIds === []) {
            return;
        }

        // Most members are current, a few lapsed, so the membership screen
        // shows every status rather than a wall of "active".
        $statuses = [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::PAUSED,
            SubscriptionStatus::EXPIRED,
            SubscriptionStatus::CANCELLED,
        ];

        foreach ($customerIds as $index => $customerId) {
            $planId = $planIds[$index % count($planIds)];
            $status = $statuses[$index % count($statuses)];

            // Start inside the last 90 days so the joins and revenue land in
            // the dashboard's default window.
            $startsOn = now()->subDays(random_int(5, 88))->startOfDay();
            $endsOn = $status === SubscriptionStatus::EXPIRED
                ? $startsOn->copy()->addMonth()->subDay()
                : $startsOn->copy()->addMonths(3);

            $included = fake()->numberBetween(4, 20);
            $used = fake()->numberBetween(0, $included);

            MembershipSubscription::create([
                'organization_id' => $orgId,
                'membership_plan_id' => $planId,
                'customer_id' => $customerId,
                'status' => $status->value,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
                'cancelled_at' => $status === SubscriptionStatus::CANCELLED ? $endsOn : null,
                'credits_granted' => $included,
                'credits_remaining' => $status === SubscriptionStatus::EXPIRED ? 0 : max(0, $included - $used),
                'is_unlimited' => false,
            ]);
        }
    }
}

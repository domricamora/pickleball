<?php

namespace App\Services\Memberships;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\MembershipCreditUsage;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Membership lifecycle and credit spending (plan.md §15).
 *
 * Spending takes a row lock on the subscription, so two bookings cannot both
 * spend the last credit. The unique (subscription, booking) index is the
 * second line of defence: the same booking can never consume credit twice.
 */
class MembershipService
{
    /**
     * Start a subscription on a plan.
     */
    public function subscribe(Customer $customer, MembershipPlan $plan, ?string $startsOn = null): MembershipSubscription
    {
        if (! $plan->is_active) {
            throw new RuntimeException('That plan is no longer available.');
        }

        $starts = $startsOn !== null
            ? Carbon::parse($startsOn)
            : now();

        $ends = $plan->billing_cycle === BillingCycle::CUSTOM
            ? $starts->copy()->addDays((int) $plan->custom_days)
            : $starts->copy()->addMonths($plan->billing_cycle->months());

        $granted = (int) ($plan->sessions_included ?? 0);

        return MembershipSubscription::create([
            'organization_id' => $plan->organization_id,
            'membership_plan_id' => $plan->id,
            'customer_id' => $customer->id,
            'status' => SubscriptionStatus::ACTIVE,
            'starts_on' => $starts,
            'ends_on' => $ends,
            'credits_granted' => $granted,
            'credits_remaining' => $granted,
            'is_unlimited' => $plan->isUnlimited(),
        ]);
    }

    /**
     * Spend credit against a booking.
     *
     * @throws RuntimeException when there is no usable credit
     */
    public function spendCredit(MembershipSubscription $subscription, ?Booking $booking = null, int $credits = 1): MembershipCreditUsage
    {
        if ($credits < 1) {
            throw new RuntimeException('A credit spend must be at least one credit.');
        }

        return DB::transaction(function () use ($subscription, $booking, $credits): MembershipCreditUsage {
            // Serialises concurrent spends on the same membership.
            $locked = MembershipSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isUsable()) {
                throw new RuntimeException('That membership is not active.');
            }

            if (! $locked->is_unlimited && $locked->credits_remaining < $credits) {
                throw new RuntimeException(
                    'This membership has no credits left. '
                    ."{$locked->credits_remaining} remaining, {$credits} needed."
                );
            }

            $usage = $locked->creditUsages()->create([
                'booking_id' => $booking?->id,
                'credits' => $credits,
                'used_at' => now(),
            ]);

            if (! $locked->is_unlimited) {
                $locked->credits_remaining = $locked->credits_remaining - $credits;
                $locked->save();
            }

            return $usage;
        }, 3);
    }

    /**
     * Return credit after a cancellation, so the member is not punished twice.
     */
    public function refundCredit(MembershipCreditUsage $usage): MembershipSubscription
    {
        return DB::transaction(function () use ($usage): MembershipSubscription {
            $locked = MembershipSubscription::query()
                ->whereKey($usage->membership_subscription_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->is_unlimited) {
                $locked->credits_remaining = min(
                    $locked->credits_granted,
                    $locked->credits_remaining + $usage->credits,
                );
                $locked->save();
            }

            return $locked;
        }, 3);
    }

    /**
     * End a membership early. Credits already spent stay spent.
     */
    public function cancel(MembershipSubscription $subscription, ?string $reason = null): MembershipSubscription
    {
        return DB::transaction(function () use ($subscription): MembershipSubscription {
            $locked = MembershipSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked->status = SubscriptionStatus::CANCELLED;
            $locked->cancelled_at = now();
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * Expire memberships whose end date has passed.
     *
     * Returns how many were expired.
     */
    public function expireDue(): int
    {
        return MembershipSubscription::query()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereDate('ends_on', '<', now())
            ->update(['status' => SubscriptionStatus::EXPIRED->value]);
    }

    /**
     * The usable membership for a customer, if any.
     */
    public function activeSubscriptionFor(Customer $customer): ?MembershipSubscription
    {
        return $customer->subscriptions()
            ->usable()
            ->orderByDesc('ends_on')
            ->first();
    }
}

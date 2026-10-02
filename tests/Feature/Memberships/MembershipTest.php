<?php

namespace Tests\Feature\Memberships;

use App\Enums\BillingCycle;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Organization;
use App\Models\User;
use App\Services\Memberships\MembershipService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 7 acceptance checks: memberships, packages and credit tracking
 * (plan.md §15).
 */
class MembershipTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();
        $court = Court::factory()->for($organization)->for($branch)->create();

        $this->customer = Customer::factory()->create([
            'organization_id' => $organization->id,
        ]);
    }

    protected function plan(array $attributes = []): MembershipPlan
    {
        return MembershipPlan::factory()->create([
            'organization_id' => $this->customer->organization_id,
        ] + $attributes);
    }

    protected function service(): MembershipService
    {
        return app(MembershipService::class);
    }

    protected function booking(): Booking
    {
        $organization = $this->customer->organization_id;
        $branch = Branch::where('organization_id', $organization)->first();
        $court = Court::where('branch_id', $branch->id)->first();

        return Booking::factory()->create([
            'organization_id' => $organization,
            'branch_id' => $branch->id,
            'court_id' => $court->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_subscribing_grants_the_included_sessions(): void
    {
        $plan = $this->plan(['sessions_included' => 8]);

        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame(8, $subscription->credits_granted);
        $this->assertSame(8, $subscription->credits_remaining);
        $this->assertFalse($subscription->is_unlimited);
    }

    public function test_a_monthly_plan_runs_for_one_month(): void
    {
        $plan = $this->plan(['billing_cycle' => BillingCycle::MONTHLY->value]);

        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->assertTrue($subscription->ends_on->greaterThan($subscription->starts_on));
        $this->assertSame(1, (int) $subscription->starts_on->diffInMonths($subscription->ends_on));
    }

    public function test_an_annual_plan_runs_for_twelve_months(): void
    {
        $plan = $this->plan(['billing_cycle' => BillingCycle::ANNUAL->value]);

        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->assertSame(12, (int) $subscription->starts_on->diffInMonths($subscription->ends_on));
    }

    public function test_an_unlimited_plan_never_runs_out(): void
    {
        $plan = $this->plan(['sessions_included' => null]);

        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->assertTrue($subscription->is_unlimited);

        for ($i = 0; $i < 25; $i++) {
            $this->service()->spendCredit($subscription);
        }

        $this->assertTrue($subscription->fresh()->is_unlimited);
    }

    public function test_spending_a_credit_reduces_the_balance(): void
    {
        $plan = $this->plan(['sessions_included' => 3]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->service()->spendCredit($subscription, $this->booking());

        $this->assertSame(2, $subscription->fresh()->credits_remaining);
    }

    public function test_credits_cannot_be_overused(): void
    {
        $plan = $this->plan(['sessions_included' => 1]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->service()->spendCredit($subscription, $this->booking());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no credits left');

        $this->service()->spendCredit($subscription, $this->booking());
    }

    public function test_credits_cannot_be_overspent_in_one_go(): void
    {
        $plan = $this->plan(['sessions_included' => 5]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->expectException(RuntimeException::class);

        // Asking for more credits than exist must fail, not go negative.
        $this->service()->spendCredit($subscription, null, 6);
    }

    public function test_the_same_booking_cannot_spend_credit_twice(): void
    {
        $plan = $this->plan(['sessions_included' => 5]);
        $subscription = $this->service()->subscribe($this->customer, $plan);
        $booking = $this->booking();

        $this->service()->spendCredit($subscription, $booking);

        // The unique (subscription, booking) index is the backstop.
        $this->expectException(QueryException::class);
        $this->service()->spendCredit($subscription, $booking);
    }

    public function test_a_cancelled_membership_cannot_spend_credit(): void
    {
        $plan = $this->plan(['sessions_included' => 5]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $this->service()->cancel($subscription);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not active');

        $this->service()->spendCredit($subscription, $this->booking());
    }

    public function test_a_paused_membership_cannot_spend_credit(): void
    {
        $plan = $this->plan(['sessions_included' => 5]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $subscription->update(['status' => SubscriptionStatus::PAUSED->value]);

        $this->expectException(RuntimeException::class);
        $this->service()->spendCredit($subscription, $this->booking());
    }

    public function test_a_membership_that_has_not_started_cannot_spend(): void
    {
        $plan = $this->plan(['sessions_included' => 5]);
        $subscription = $this->service()->subscribe($this->customer, $plan, '2026-06-01');

        $this->expectException(RuntimeException::class);
        $this->service()->spendCredit($subscription, $this->booking());
    }

    public function test_an_expired_membership_cannot_spend_credit(): void
    {
        $subscription = MembershipSubscription::factory()->expired()->create([
            'organization_id' => $this->customer->organization_id,
            'customer_id' => $this->customer->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->service()->spendCredit($subscription, $this->booking());
    }

    public function test_refunding_a_credit_returns_it(): void
    {
        $plan = $this->plan(['sessions_included' => 2]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $usage = $this->service()->spendCredit($subscription, $this->booking());
        $this->assertSame(1, $subscription->fresh()->credits_remaining);

        $this->service()->refundCredit($usage);

        $this->assertSame(2, $subscription->fresh()->credits_remaining);
    }

    public function test_a_refund_cannot_inflate_the_balance_beyond_the_grant(): void
    {
        $plan = $this->plan(['sessions_included' => 2]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $usage = $this->service()->spendCredit($subscription, $this->booking());

        // Refund the same spend twice; the balance must cap at the grant.
        $this->service()->refundCredit($usage);
        $this->service()->refundCredit($usage);

        $this->assertSame(2, $subscription->fresh()->credits_remaining);
    }

    public function test_expiring_marks_lapsed_memberships(): void
    {
        // Still flagged active, but the end date has already passed.
        $lapsed = MembershipSubscription::factory()->create([
            'organization_id' => $this->customer->organization_id,
            'customer_id' => $this->customer->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_on' => now()->subMonths(3)->toDateString(),
            'ends_on' => now()->subDay()->toDateString(),
        ]);

        $current = $this->service()->subscribe($this->customer, $this->plan());

        $this->assertSame(1, $this->service()->expireDue());

        $this->assertSame(SubscriptionStatus::EXPIRED, $lapsed->fresh()->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $current->fresh()->status);
    }

    public function test_the_active_membership_can_be_found_for_a_customer(): void
    {
        $plan = $this->plan(['sessions_included' => 4]);
        $subscription = $this->service()->subscribe($this->customer, $plan);

        $found = $this->service()->activeSubscriptionFor($this->customer);

        $this->assertNotNull($found);
        $this->assertSame($subscription->id, $found->id);
    }

    public function test_a_customer_with_no_membership_has_none_active(): void
    {
        $this->assertNull($this->service()->activeSubscriptionFor($this->customer));
    }

    public function test_a_member_discount_reduces_the_rate(): void
    {
        $plan = $this->plan(['discount_percent' => '10.00']);

        $this->assertSame(360.0, $plan->discount(400.0));
    }

    public function test_an_inactive_plan_cannot_be_subscribed_to(): void
    {
        $plan = $this->plan(['is_active' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no longer available');

        $this->service()->subscribe($this->customer, $plan);
    }

    public function test_memberships_are_isolated_between_tenants(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $mine = $this->service()->subscribe($this->customer, $this->plan());

        $otherOrg = Organization::factory()->create();
        $otherCustomer = Customer::factory()->create(['organization_id' => $otherOrg->id]);
        $this->service()->subscribe($otherCustomer, MembershipPlan::factory()->create([
            'organization_id' => $otherOrg->id,
        ]));

        $otherStaff = User::factory()->forTenant($otherOrg, Role::FACILITY_OWNER)->create();
        $this->actingAs($otherStaff);

        // They see exactly their own membership, and never mine.
        $visible = MembershipSubscription::query()->pluck('id');

        $this->assertCount(1, $visible, 'Another facility must see only its own membership.');
        $this->assertNotContains($mine->id, $visible);
    }
}

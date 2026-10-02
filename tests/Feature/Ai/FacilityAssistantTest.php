<?php

namespace Tests\Feature\Ai;

use App\Enums\BookingStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Organization;
use App\Models\Product;
use App\Services\Ai\AiGuard;
use App\Services\Ai\FacilityAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 14 acceptance checks: AI assists, it does not control.
 *
 * The most important tests here are the refusals: an assistant that can move
 * money or reserve a court is a liability (plan.md §22).
 */
class FacilityAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected FacilityAssistant $assistant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->for($this->organization)->create();
        $court = Court::factory()->for($this->organization)->for($this->branch)->create();

        foreach (range(0, 6) as $weekday) {
            CourtSchedule::create([
                'organization_id' => $this->organization->id,
                'court_id' => $court->id,
                'weekday' => $weekday,
                'opens_at' => '08:00:00',
                'closes_at' => '22:00:00',
                'is_closed' => false,
            ]);
        }

        $this->assistant = app(FacilityAssistant::class);
    }

    protected function booking(int $hour, string $status = 'completed'): Booking
    {
        $court = Court::where('organization_id', $this->organization->id)->firstOrFail();

        return Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $court->id,
            'status' => BookingStatus::from($status)->value,
            'starts_at' => now()->subDays(2)->setTime($hour, 0),
        ]);
    }

    public function test_the_assistant_may_not_move_money(): void
    {
        $guard = new AiGuard;

        foreach (['take_payment', 'issue_refund', 'change_price'] as $action) {
            $this->assertFalse($guard->isPermitted($action));
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must go through the normal validated workflow');

        $guard->assertPermitted('issue_refund');
    }

    public function test_the_assistant_may_not_touch_bookings_or_stock(): void
    {
        $guard = new AiGuard;

        foreach (AiGuard::FORBIDDEN_ACTIONS as $action) {
            $this->assertFalse($guard->isPermitted($action), "{$action} must be forbidden");
        }
    }

    public function test_reading_is_permitted(): void
    {
        $guard = new AiGuard;

        $this->assertTrue($guard->isPermitted('read_availability'));
        $this->assertTrue($guard->isPermitted('summarise_revenue'));
    }

    public function test_the_assistant_must_be_scoped_to_one_facility(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('scoped to a single facility');

        (new AiGuard)->assertScope(null);
    }

    public function test_it_answers_an_availability_question(): void
    {
        $result = $this->assistant->answer($this->organization, 'Which courts are available?');

        $this->assertSame('availability', $result['intent']);
        $this->assertStringContainsString('open slots today', $result['answer']);
    }

    public function test_it_answers_with_busiest_hours(): void
    {
        $this->booking(18);
        $this->booking(18);
        $this->booking(19);

        $result = $this->assistant->answer($this->organization, 'Which hours are most popular?');

        $this->assertSame('busiest_hours', $result['intent']);
        $this->assertStringContainsString('Busiest hours', $result['answer']);
        $this->assertSame(2, $result['data']['by_hour'][18]);
    }

    public function test_busiest_hours_are_readable_not_24_hour(): void
    {
        $this->booking(18);

        $result = $this->assistant->answer($this->organization, 'busiest hours');

        $this->assertStringContainsString('6:00 PM', $result['answer']);
    }

    public function test_cancelled_bookings_do_not_make_a_court_look_busy(): void
    {
        $this->booking(18, 'cancelled');

        $result = $this->assistant->answer($this->organization, 'busiest hours');

        $this->assertStringContainsString('No booking history', $result['answer']);
    }

    public function test_it_reports_expiring_memberships(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
        ]);

        $plan = MembershipPlan::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        MembershipSubscription::factory()->create([
            'organization_id' => $this->organization->id,
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_on' => now()->addDays(10)->toDateString(),
        ]);

        $result = $this->assistant->answer($this->organization, 'Which memberships are expiring?');

        $this->assertSame('memberships_expiring', $result['intent']);
        $this->assertSame(1, $result['data']['count']);
        $this->assertContains('Ana Reyes', $result['data']['members']);
    }

    public function test_it_reports_revenue_in_peso(): void
    {
        $result = $this->assistant->answer($this->organization, 'How is revenue this month?');

        $this->assertSame('revenue', $result['intent']);
        $this->assertStringContainsString('₱', $result['answer']);
    }

    public function test_it_reports_low_stock(): void
    {
        Product::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Tourney Balls',
            'stock' => 1,
            'reorder_level' => 5,
        ]);

        $result = $this->assistant->answer($this->organization, 'Any inventory risk?');

        $this->assertSame('inventory_risk', $result['intent']);
        $this->assertStringContainsString('Tourney Balls', $result['data']['low_stock'][0]);
    }

    public function test_an_unknown_question_is_answered_safely(): void
    {
        $result = $this->assistant->answer($this->organization, 'What is the meaning of life?');

        $this->assertSame('unknown', $result['intent']);
        $this->assertStringContainsString('I can answer questions about', $result['answer']);
    }

    public function test_the_assistant_never_sees_another_tenants_data(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherBranch = Branch::factory()->for($otherOrg)->create();
        $otherCourt = Court::factory()->for($otherOrg)->for($otherBranch)->create();

        MembershipPlan::factory()->create(['organization_id' => $otherOrg->id]);
        MembershipSubscription::factory()->create([
            'organization_id' => $otherOrg->id,
            'customer_id' => Customer::factory()->create(['organization_id' => $otherOrg->id])->id,
            'membership_plan_id' => MembershipPlan::where('organization_id', $otherOrg->id)->firstOrFail()->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_on' => now()->addDays(5)->toDateString(),
        ]);

        $result = $this->assistant->answer($this->organization, 'Which memberships are expiring?');

        $this->assertSame(0, $result['data']['count'], 'Another facility\'s members must never appear.');
    }
}

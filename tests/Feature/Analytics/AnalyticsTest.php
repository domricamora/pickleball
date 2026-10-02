<?php

namespace Tests\Feature\Analytics;

use App\Enums\BookingStatus;
use App\Enums\FunnelStep;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Organization;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 15 acceptance checks: product analytics and the funnel
 * (plan.md §23).
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected AnalyticsService $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->analytics = app(AnalyticsService::class);
    }

    public function test_an_event_is_recorded(): void
    {
        $event = $this->analytics->track(
            FunnelStep::COURT_VIEW,
            $this->organization,
            ['court_id' => 7],
            'session-1',
            'mobile',
            'facebook.com',
        );

        $this->assertSame(FunnelStep::COURT_VIEW, $event->name);
        $this->assertSame(7, $event->properties['court_id']);
        $this->assertNotNull($event->occurred_at);
    }

    public function test_a_visitor_needs_no_facility(): void
    {
        // Someone browsing the marketing site has not chosen a facility yet.
        $event = $this->analytics->track(FunnelStep::VISITOR);

        $this->assertNull($event->organization_id);
    }

    public function test_a_long_referrer_is_truncated(): void
    {
        $event = $this->analytics->track(
            FunnelStep::VISITOR,
            null,
            [],
            null,
            null,
            str_repeat('a', 300),
        );

        $this->assertSame(128, strlen($event->referrer));
    }

    public function test_the_funnel_reports_every_step(): void
    {
        foreach (range(1, 10) as $i) {
            $this->analytics->track(FunnelStep::VISITOR, $this->organization);
        }
        foreach (range(1, 5) as $i) {
            $this->analytics->track(FunnelStep::FACILITY_VIEW, $this->organization);
        }
        $this->analytics->track(FunnelStep::COURT_VIEW, $this->organization);

        $funnel = $this->analytics->funnel($this->organization);

        $this->assertCount(6, $funnel);
        $this->assertSame(10, $funnel[0]['count']);
        $this->assertSame(5, $funnel[1]['count']);
        $this->assertSame(50.0, $funnel[1]['conversion'], '5 of 10 is 50%.');
        $this->assertSame(0, $funnel[5]['count']);
    }

    public function test_the_first_step_has_no_conversion(): void
    {
        $this->analytics->track(FunnelStep::VISITOR, $this->organization);

        $funnel = $this->analytics->funnel($this->organization);

        $this->assertNull($funnel[0]['conversion'], 'The first step has nothing before it.');
    }

    public function test_division_by_zero_is_avoided(): void
    {
        $this->analytics->track(FunnelStep::VISITOR, $this->organization);

        $funnel = $this->analytics->funnel($this->organization);

        // 1 visitor, 0 facility views: a real zero, not a division by zero.
        $this->assertSame(0.0, $funnel[1]['conversion']);

        // 0 into 0 is genuinely undefined and must be null, not NaN.
        $this->assertNull($funnel[2]['conversion']);
    }

    public function test_an_empty_funnel_is_all_zero(): void
    {
        $funnel = $this->analytics->funnel($this->organization);

        $this->assertSame(0, $funnel[0]['count']);
        $this->assertNull($funnel[0]['conversion']);
    }

    public function test_the_funnel_never_closes_upwards(): void
    {
        // A broken funnel where more people "book" than "view" a court.
        $this->analytics->track(FunnelStep::COURT_VIEW, $this->organization);
        $this->analytics->track(FunnelStep::BOOKING_STARTED, $this->organization);
        $this->analytics->track(FunnelStep::BOOKING_COMPLETED, $this->organization);
        $this->analytics->track(FunnelStep::BOOKING_COMPLETED, $this->organization);

        $funnel = $this->analytics->funnel($this->organization);

        $completed = $funnel[4];

        // 1 booking started, 2 completed: the step genuinely grew, and the
        // report must show that rather than cap it at 100%.
        $this->assertSame(200.0, $completed['conversion']);
    }

    public function test_referrers_are_ranked(): void
    {
        $this->analytics->track(FunnelStep::VISITOR, null, [], null, null, 'facebook.com');
        $this->analytics->track(FunnelStep::VISITOR, null, [], null, null, 'facebook.com');
        $this->analytics->track(FunnelStep::VISITOR, null, [], null, null, 'google.com');

        $referrers = $this->analytics->referrers();

        $this->assertSame(2, $referrers['facebook.com']);
        $this->assertSame(1, $referrers['google.com']);
        $this->assertSame('facebook.com', array_key_first($referrers));
    }

    public function test_referrers_ignore_non_visitor_events(): void
    {
        $this->analytics->track(FunnelStep::COURT_VIEW, null, [], null, null, 'google.com');

        $this->assertSame([], $this->analytics->referrers());
    }

    public function test_analytics_never_mix_tenants(): void
    {
        $otherOrg = Organization::factory()->create();

        $this->analytics->track(FunnelStep::VISITOR, $this->organization);
        $this->analytics->track(FunnelStep::VISITOR, $otherOrg);
        $this->analytics->track(FunnelStep::VISITOR, $otherOrg);

        $funnel = $this->analytics->funnel($this->organization);

        $this->assertSame(1, $funnel[0]['count'], 'Another facility\'s traffic must not appear.');
    }

    public function test_court_utilisation_counts_only_played_bookings(): void
    {
        $branch = Branch::factory()->for($this->organization)->create();
        $court = Court::factory()->for($this->organization)->for($branch)->create([
            'name' => 'Court A',
        ]);

        Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $branch->id,
            'court_id' => $court->id,
            'status' => BookingStatus::COMPLETED->value,
            'duration_minutes' => 60,
            'starts_at' => now()->subDays(2),
        ]);

        Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $branch->id,
            'court_id' => $court->id,
            'status' => BookingStatus::CANCELLED->value,
            'duration_minutes' => 600,
            'starts_at' => now()->subDays(1),
        ]);

        $utilisation = $this->analytics->courtUtilisation($this->organization);

        $this->assertArrayHasKey('Court A', $utilisation);
        $this->assertLessThan(2.0, $utilisation['Court A'], 'A cancellation must not count as play.');
    }

    public function test_utilisation_is_empty_with_no_courts(): void
    {
        $this->assertSame([], $this->analytics->courtUtilisation($this->organization));
    }
}

<?php

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMatch;
use App\Models\EventRegistration;
use App\Models\MembershipPlan;
use App\Models\Organization;
use App\Services\Events\EventService;
use App\Services\Memberships\MembershipService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 8 acceptance checks: events, registration, brackets and results
 * (plan.md §16).
 *
 * Match scores are GAME counts, not points: a best-of-three is recorded 2-0 or
 * 2-1, which is how pickleball is actually scored and refereed.
 */
class EventTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $branch = Branch::factory()->for($this->organization)->create();
        Court::factory()->for($this->organization)->for($branch)->create();

        $this->customer = Customer::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    protected function event(array $attributes = []): Event
    {
        $branch = Branch::where('organization_id', $this->organization->id)->firstOrFail();

        return Event::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $branch->id,
        ] + $attributes);
    }

    protected function anotherCustomer(): Customer
    {
        return Customer::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    protected function service(): EventService
    {
        return app(EventService::class);
    }

    /**
     * A match between two fresh registrations, with optional overrides.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: EventMatch, 1: EventRegistration, 2: EventRegistration}
     */
    protected function match(array $attributes = []): array
    {
        $event = $this->event();

        return $this->buildMatch(
            $event,
            null,
            $attributes,
            $this->service()->register($event, $this->customer),
            $this->service()->register($event, $this->anotherCustomer()),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: EventMatch, 1: EventRegistration, 2: EventRegistration}
     */
    protected function buildMatch(
        Event $event,
        ?EventDivision $division,
        array $attributes = [],
        ?EventRegistration $a = null,
        ?EventRegistration $b = null,
    ): array {
        $a ??= $this->service()->register($event, $this->customer);
        $b ??= $this->service()->register($event, $this->anotherCustomer());

        $match = EventMatch::create(array_merge([
            'event_id' => $event->id,
            'event_division_id' => $division?->id,
            'team_a_registration_id' => $a->id,
            'team_b_registration_id' => $b->id,
            'best_of' => 3,
        ], $attributes));

        return [$match, $a, $b];
    }

    public function test_a_player_can_register_for_an_event(): void
    {
        $event = $this->event(['capacity' => 8]);

        $registration = $this->service()->register($event, $this->customer);

        $this->assertSame('registered', $registration->status);
        $this->assertSame('350.00', $registration->amount_paid);
    }

    public function test_a_player_cannot_register_twice(): void
    {
        $event = $this->event();

        $this->service()->register($event, $this->customer);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already registered');

        $this->service()->register($event, $this->customer);
    }

    public function test_a_full_event_waitlists_instead_of_refusing(): void
    {
        $event = $this->event(['capacity' => 1]);

        $first = $this->service()->register($event, $this->customer);
        $second = $this->service()->register($event, $this->anotherCustomer());

        $this->assertSame('registered', $first->status);
        $this->assertSame('waitlisted', $second->status);
        $this->assertSame('0.00', $second->amount_paid, 'A waitlisted player pays nothing yet.');
    }

    public function test_withdrawing_promotes_the_next_waitlisted_player(): void
    {
        $event = $this->event(['capacity' => 1]);

        $first = $this->service()->register($event, $this->customer);
        $waiting = $this->service()->register($event, $this->anotherCustomer());

        $this->assertSame('waitlisted', $waiting->status);

        $this->service()->withdraw($first);

        $this->assertSame('withdrawn', $first->fresh()->status);
        $this->assertSame('registered', $waiting->fresh()->status, 'The freed place must be offered on.');
        $this->assertSame('350.00', $waiting->fresh()->amount_paid);
    }

    public function test_a_cancelled_event_takes_no_registrations(): void
    {
        $event = $this->event(['status' => EventStatus::CANCELLED->value]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not accepting entries');

        $this->service()->register($event, $this->customer);
    }

    public function test_a_draft_event_takes_no_registrations(): void
    {
        $event = $this->event(['status' => EventStatus::DRAFT->value]);

        $this->expectException(RuntimeException::class);
        $this->service()->register($event, $this->customer);
    }

    public function test_a_members_only_event_refuses_non_members(): void
    {
        $event = $this->event(['is_members_only' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('members only');

        $this->service()->register($event, $this->customer);
    }

    public function test_a_members_only_event_admits_a_member(): void
    {
        $event = $this->event(['is_members_only' => true]);

        $plan = MembershipPlan::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        app(MembershipService::class)->subscribe($this->customer, $plan);

        $registration = $this->service()->register($event, $this->customer);

        $this->assertSame('registered', $registration->status);
    }

    public function test_a_division_filters_by_rating(): void
    {
        $division = $this->event()->divisions()->create([
            'name' => '3.0 Mixed',
            'min_rating' => 3.0,
            'max_rating' => 3.99,
        ]);

        $this->assertTrue($division->accepts(3.0));
        $this->assertTrue($division->accepts(3.5));
        $this->assertFalse($division->accepts(2.9));
        $this->assertFalse($division->accepts(4.1));
    }

    public function test_an_open_division_accepts_any_rating(): void
    {
        $division = $this->event()->divisions()->create(['name' => 'Open']);

        $this->assertTrue($division->accepts(1.0));
        $this->assertTrue($division->accepts(5.0));
    }

    public function test_a_match_result_can_be_recorded(): void
    {
        [$match] = $this->match();

        $result = $this->service()->recordResult($match, 2, 1);

        $this->assertSame('completed', $result->status);
        $this->assertSame(2, $result->team_a_score);
    }

    public function test_a_match_cannot_end_in_a_draw(): void
    {
        [$match] = $this->match();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot end in a draw');

        $this->service()->recordResult($match, 1, 1);
    }

    public function test_a_best_of_three_cannot_be_won_by_one_game(): void
    {
        [$match] = $this->match(['best_of' => 3]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be won');

        $this->service()->recordResult($match, 1, 0);
    }

    public function test_a_longer_best_of_allows_a_three_game_win(): void
    {
        [$match] = $this->match(['best_of' => 5]);

        $result = $this->service()->recordResult($match, 3, 2);

        $this->assertSame('completed', $result->status);
    }

    public function test_an_unreachable_score_is_refused(): void
    {
        [$match] = $this->match(['best_of' => 3]);

        // 9 games cannot fit in a best of three.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be reached');

        $this->service()->recordResult($match, 9, 4);
    }

    public function test_a_match_cannot_be_recorded_twice(): void
    {
        [$match] = $this->match();

        $this->service()->recordResult($match, 2, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been recorded');

        $this->service()->recordResult($match, 1, 2);
    }

    public function test_a_bye_cannot_be_scored(): void
    {
        [$match] = $this->match(['status' => 'bye']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bye cannot be scored');

        $this->service()->recordResult($match, 2, 0);
    }

    public function test_the_winner_is_derived_from_the_score(): void
    {
        [$match] = $this->match();

        $result = $this->service()->recordResult($match, 2, 1);

        $winner = $result->winner();

        $this->assertNotNull($winner);
        $this->assertSame($match->team_a_registration_id, $winner->id);
        $this->assertSame('2–1', $result->scoreLine());
    }

    public function test_an_unplayed_match_has_no_winner(): void
    {
        [$match] = $this->match();

        $this->assertNull($match->winner());
        $this->assertSame('v', $match->scoreLine());
    }

    public function test_standings_rank_by_wins(): void
    {
        $event = $this->event();
        $division = $event->divisions()->create(['name' => 'Open']);

        $a = $this->service()->register($event, $this->customer);
        $b = $this->service()->register($event, $this->anotherCustomer());
        $c = $this->service()->register($event, $this->anotherCustomer());

        // A beats B, then A beats C. A must top the table with 6 points.
        $this->service()->recordResult($this->buildMatch($event, $division, [], $a, $b)[0], 2, 1);
        $this->service()->recordResult($this->buildMatch($event, $division, [], $a, $c)[0], 2, 0);

        $standings = $this->service()->standings($division);

        $this->assertCount(3, $standings);
        $this->assertSame($a->id, $standings[0]['registration']->id);
        $this->assertSame(2, $standings[0]['wins']);
        $this->assertSame(6, $standings[0]['points']);
    }

    public function test_singles_and_doubles_share_a_match_shape(): void
    {
        $event = $this->event(['format' => 'mixed_doubles']);

        $registration = $this->service()->register($event, $this->customer, null, 'Partner');
        [$match] = $this->buildMatch(
            $event,
            null,
            [],
            $registration,
            $this->service()->register($event, $this->anotherCustomer()),
        );

        // A doubles entry is one registration carrying a partner, so the match
        // is identical in shape to a singles one.
        $this->assertStringContainsString('/', $registration->displayName());
        $this->assertSame($registration->id, $match->team_a_registration_id);
    }

    public function test_the_database_blocks_a_double_registration(): void
    {
        $event = $this->event();

        $event->registrations()->create([
            'customer_id' => $this->customer->id,
            'status' => 'registered',
        ]);

        // Even bypassing the service, the unique index holds.
        $this->expectException(QueryException::class);

        $event->registrations()->create([
            'customer_id' => $this->customer->id,
            'status' => 'registered',
        ]);
    }

    public function test_a_free_event_reports_no_fee(): void
    {
        $this->assertSame('Free', $this->event(['fee' => 0])->formattedFee());
        $this->assertSame('₱350.00', $this->event(['fee' => 350])->formattedFee());
    }

    public function test_the_schedule_is_rendered_in_manila_time(): void
    {
        $event = $this->event([
            'starts_at' => '2026-03-14 09:00:00',
            'ends_at' => '2026-03-14 13:00:00',
        ]);

        $this->assertStringContainsString('9:00 AM', $event->schedule());
        $this->assertStringContainsString('1:00 PM', $event->schedule());
    }
}

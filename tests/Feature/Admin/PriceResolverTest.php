<?php

namespace Tests\Feature\Admin;

use App\Enums\BlockType;
use App\Enums\PriceType;
use App\Models\Branch;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtPrice;
use App\Models\Organization;
use App\Services\PriceResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pricing resolution (plan.md §11, §13).
 */
class PriceResolverTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();
        $this->court = Court::factory()->for($organization)->for($branch)->create();

        $this->court->forceFill([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ])->save();
    }

    /** Publish a branch-wide rate. */
    protected function price(PriceType $type, string $amount, array $overrides = []): CourtPrice
    {
        return CourtPrice::create([
            'organization_id' => $this->court->organization_id,
            'branch_id' => $this->court->branch_id,
            'court_id' => null,
            'type' => $type->value,
            'amount' => $amount,
            ...$overrides,
        ]);
    }

    public function test_no_price_returns_null_rather_than_a_guess(): void
    {
        $resolver = app(PriceResolver::class);

        // Must never invent a price; the caller refuses the booking instead.
        $this->assertNull($resolver->amountFor($this->court, now()));
    }

    public function test_weekday_and_weekend_rates_are_distinguished(): void
    {
        $this->price(PriceType::WEEKDAY, '300.00');
        $this->price(PriceType::WEEKEND, '450.00');

        $resolver = app(PriceResolver::class);

        $this->assertEquals(300.0, $resolver->amountFor($this->court, Carbon::parse('2026-03-04'))); // Wed
        $this->assertEquals(450.0, $resolver->amountFor($this->court, Carbon::parse('2026-03-07'))); // Sat
    }

    public function test_a_court_specific_rate_beats_the_branch_default(): void
    {
        $this->price(PriceType::WEEKDAY, '300.00');

        CourtPrice::create([
            'organization_id' => $this->court->organization_id,
            'branch_id' => $this->court->branch_id,
            'court_id' => $this->court->id,
            'type' => PriceType::WEEKDAY->value,
            'amount' => '250.00',
        ]);

        $resolver = app(PriceResolver::class);

        $this->assertEquals(
            250.0,
            $resolver->amountFor($this->court, Carbon::parse('2026-03-04')),
            'A court-specific rate must win over the branch default.',
        );
    }

    public function test_a_peak_rate_beats_a_weekday_rate_within_its_window(): void
    {
        $this->price(PriceType::WEEKDAY, '300.00');
        $this->price(PriceType::PEAK, '500.00', [
            'starts_at_hour' => 17,
            'ends_at_hour' => 21,
        ]);

        $resolver = app(PriceResolver::class);
        $date = Carbon::parse('2026-03-04');

        $this->assertEquals(500.0, $resolver->amountFor($this->court, $date, 18), '18:00 is inside the peak window.');
        $this->assertEquals(300.0, $resolver->amountFor($this->court, $date, 15), '15:00 is outside it.');
    }

    public function test_an_inactive_rate_is_ignored(): void
    {
        $this->price(PriceType::WEEKDAY, '300.00', ['is_active' => false]);

        $resolver = app(PriceResolver::class);

        $this->assertNull($resolver->amountFor($this->court, Carbon::parse('2026-03-04')));
    }

    public function test_a_member_rate_is_used_explicitly_and_falls_back(): void
    {
        $this->price(PriceType::WEEKDAY, '300.00');
        $this->price(PriceType::MEMBER, '200.00');

        $resolver = app(PriceResolver::class);
        $date = Carbon::parse('2026-03-04');

        // Members get their rate...
        $this->assertEquals(
            200.0,
            $resolver->amountForAudience($this->court, PriceType::MEMBER, $date),
        );

        // ...and a guest with no guest rate falls back to the weekday rate.
        $this->assertEquals(
            300.0,
            $resolver->amountForAudience($this->court, PriceType::GUEST, $date),
        );
    }

    public function test_a_holiday_rate_applies_only_when_the_facility_marks_a_holiday(): void
    {
        $this->price(PriceType::WEEKDAY, '300.00');
        $this->price(PriceType::HOLIDAY, '600.00');

        $resolver = app(PriceResolver::class);
        $date = Carbon::parse('2026-12-25');

        // No holiday declared yet, so the normal weekday rate applies.
        $this->assertFalse($resolver->isHoliday($this->court, $date));
        $this->assertEquals(300.0, $resolver->amountFor($this->court, $date));

        // The facility declares the holiday itself (a local fiesta need not
        // appear on any national calendar).
        CourtBlock::create([
            'organization_id' => $this->court->organization_id,
            'branch_id' => $this->court->branch_id,
            'type' => BlockType::HOLIDAY->value,
            'reason' => 'Christmas Day',
            'starts_on' => '2026-12-25',
            'ends_on' => '2026-12-25',
        ]);

        $this->assertTrue($resolver->isHoliday($this->court, $date));
        $this->assertEquals(600.0, $resolver->amountFor($this->court, $date));
    }
}

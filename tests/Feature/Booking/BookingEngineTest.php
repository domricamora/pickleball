<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\BookCourt;
use App\Actions\Booking\ChangeBookingStatus;
use App\Enums\BookingStatus;
use App\Enums\PriceType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtPrice;
use App\Models\CourtSchedule;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 4 acceptance checks: the booking engine (plan.md §12, §31).
 */
class BookingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court;

    /** A Wednesday. */
    protected const DATE = '2026-03-04';

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();

        $this->court = Court::factory()->for($organization)->for($branch)->create([
            'status' => 'available',
        ]);

        // Open every day so each test isolates the rule under test.
        foreach (range(0, 6) as $weekday) {
            CourtSchedule::create([
                'organization_id' => $organization->id,
                'court_id' => $this->court->id,
                'weekday' => $weekday,
                'opens_at' => '08:00:00',
                'closes_at' => '22:00:00',
                'is_closed' => false,
            ]);
        }

        foreach ([PriceType::WEEKDAY, PriceType::WEEKEND] as $type) {
            CourtPrice::create([
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'type' => $type->value,
                'amount' => '400.00',
            ]);
        }
    }

    /** Manila-local datetime helper. */
    protected function manila(string $time): Carbon
    {
        return Carbon::parse(self::DATE.' '.$time, config('platform.locale.timezone'));
    }

    protected function book(string $start, string $end): Booking
    {
        return app(BookCourt::class)->handle($this->court, [
            'starts_at' => $this->manila($start),
            'ends_at' => $this->manila($end),
        ]);
    }

    public function test_a_booking_is_created_and_confirmed(): void
    {
        $booking = $this->book('18:00', '19:00');

        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame('400.00', $booking->amount);
        $this->assertSame(60, $booking->duration_minutes);
        $this->assertSame($this->court->organization_id, $booking->organization_id);
        $this->assertNotEmpty($booking->reference);

        // The audit trail records the creation.
        $this->assertSame(1, $booking->statusHistory()->count());
    }

    public function test_an_overlapping_booking_is_rejected(): void
    {
        $this->book('18:00', '19:00');

        $this->expectException(RuntimeException::class);
        $this->book('18:30', '19:30');
    }

    public function test_a_booking_inside_another_is_rejected(): void
    {
        $this->book('18:00', '20:00');

        $this->expectException(RuntimeException::class);
        $this->book('18:30', '19:00');
    }

    public function test_back_to_back_bookings_are_allowed(): void
    {
        // Half-open intervals: 18:00-19:00 and 19:00-20:00 do not overlap.
        $first = $this->book('18:00', '19:00');
        $second = $this->book('19:00', '20:00');

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Booking::query()->count());
    }

    public function test_a_second_booking_on_the_same_slot_never_lands(): void
    {
        $this->book('18:00', '19:00');

        try {
            $this->book('18:00', '19:00');
            $this->fail('The second booking should have been refused.');
        } catch (RuntimeException) {
            // Expected.
        }

        // Exactly one booking exists, so the check and insert cannot diverge.
        $this->assertSame(1, Booking::query()->count());
    }

    public function test_booking_outside_opening_hours_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->book('06:00', '07:00');
    }

    public function test_booking_past_closing_time_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->book('21:30', '23:00');
    }

    public function test_an_inverted_time_range_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->book('19:00', '18:00');
    }

    public function test_a_court_under_maintenance_cannot_be_booked(): void
    {
        $this->court->update(['status' => 'maintenance']);

        $this->expectException(RuntimeException::class);
        $this->book('18:00', '19:00');
    }

    public function test_a_maintenance_block_removes_the_slot(): void
    {
        CourtBlock::create([
            'organization_id' => $this->court->organization_id,
            'branch_id' => $this->court->branch_id,
            'court_id' => $this->court->id,
            'type' => 'maintenance',
            'reason' => 'Net replacement',
            'starts_on' => self::DATE,
            'ends_on' => self::DATE,
            'starts_at' => '18:00:00',
            'ends_at' => '20:00:00',
        ]);

        $this->expectException(RuntimeException::class);
        $this->book('18:00', '19:00');
    }

    public function test_a_booking_is_refused_when_no_price_is_published(): void
    {
        CourtPrice::query()->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has not published a price');
        $this->book('18:00', '19:00');
    }

    public function test_a_cancelled_booking_releases_its_slot(): void
    {
        $booking = $this->book('18:00', '19:00');

        app(ChangeBookingStatus::class)->cancel($booking);

        // The slot is free again, so the same window can be rebooked.
        $second = $this->book('18:00', '19:00');
        $this->assertNotSame($booking->id, $second->id);
    }
}

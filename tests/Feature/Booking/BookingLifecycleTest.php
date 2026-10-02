<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\BookCourt;
use App\Actions\Booking\ChangeBookingStatus;
use App\Actions\Booking\RescheduleBooking;
use App\Enums\BookingStatus;
use App\Enums\PriceType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\CourtPrice;
use App\Models\CourtSchedule;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Booking lifecycle: cancel, reschedule, check-in and no-show (plan.md §12).
 */
class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court;

    protected const DATE = '2026-03-04';

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();

        $this->court = Court::factory()->for($organization)->for($branch)->create([
            'status' => 'available',
        ]);

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

    protected function manila(string $time): Carbon
    {
        return Carbon::parse(self::DATE.' '.$time, config('platform.locale.timezone'));
    }

    protected function book(string $start = '18:00', string $end = '19:00'): Booking
    {
        return app(BookCourt::class)->handle($this->court, [
            'starts_at' => $this->manila($start),
            'ends_at' => $this->manila($end),
        ]);
    }

    public function test_a_booking_can_be_cancelled_and_records_the_actor(): void
    {
        $staff = User::factory()->create();
        $booking = $this->book();

        $cancelled = app(ChangeBookingStatus::class)
            ->cancel($booking, $staff, 'Player changed plans');

        $this->assertSame(BookingStatus::CANCELLED, $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertSame($staff->id, $cancelled->cancelled_by);
        $this->assertSame('Player changed plans', $cancelled->cancellation_reason);

        // The transition is auditable.
        $history = $cancelled->statusHistory()->orderByDesc('id')->first();
        $this->assertSame(BookingStatus::CANCELLED, $history->to_status);
        $this->assertSame(BookingStatus::CONFIRMED, $history->from_status);
    }

    public function test_a_booking_can_be_checked_in_and_completed(): void
    {
        $booking = $this->book();
        $action = app(ChangeBookingStatus::class);

        $checkedIn = $action->handle($booking, BookingStatus::CHECKED_IN);
        $this->assertSame(BookingStatus::CHECKED_IN, $checkedIn->status);
        $this->assertNotNull($checkedIn->checked_in_at);

        $completed = $action->handle($checkedIn, BookingStatus::COMPLETED);
        $this->assertSame(BookingStatus::COMPLETED, $completed->status);
        $this->assertNotNull($completed->completed_at);
    }

    public function test_a_no_show_can_be_recorded(): void
    {
        $booking = $this->book();

        $noShow = app(ChangeBookingStatus::class)
            ->handle($booking, BookingStatus::NO_SHOW);

        $this->assertSame(BookingStatus::NO_SHOW, $noShow->status);
        $this->assertNotNull($noShow->no_show_at);
    }

    public function test_a_cancelled_booking_can_no_longer_change_status(): void
    {
        $booking = $this->book();
        app(ChangeBookingStatus::class)->cancel($booking);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('can no longer be changed');

        app(ChangeBookingStatus::class)->handle($booking, BookingStatus::CHECKED_IN);
    }

    public function test_a_booking_can_be_rescheduled_to_a_free_slot(): void
    {
        $booking = $this->book('18:00', '19:00');

        $moved = app(RescheduleBooking::class)->handle(
            $booking,
            $this->manila('20:00'),
            $this->manila('21:00'),
        );

        $this->assertTrue($moved->starts_at->isSameDay($this->manila('20:00')));
        $this->assertSame(60, $moved->duration_minutes);
        $this->assertSame(BookingStatus::CONFIRMED, $moved->status);
    }

    public function test_a_booking_cannot_be_moved_onto_an_occupied_slot(): void
    {
        $first = $this->book('18:00', '19:00');
        $this->book('20:00', '21:00');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no longer available');

        app(RescheduleBooking::class)->handle(
            $first,
            $this->manila('20:00'),
            $this->manila('21:00'),
        );
    }

    public function test_a_booking_can_be_rescheduled_onto_its_own_slot(): void
    {
        $booking = $this->book('18:00', '19:00');

        // Without excluding itself the booking would clash with itself.
        $moved = app(RescheduleBooking::class)->handle(
            $booking,
            $this->manila('18:00'),
            $this->manila('19:00'),
        );

        $this->assertSame($booking->id, $moved->id);
    }

    public function test_rescheduling_reprices_the_booking(): void
    {
        // Booked at the rate published at the time.
        $booking = $this->book('18:00', '19:00');
        $this->assertSame('400.00', $booking->amount);

        // The facility then raises its rate.
        CourtPrice::query()->update(['amount' => '500.00']);

        $moved = app(RescheduleBooking::class)->handle(
            $booking,
            $this->manila('20:00'),
            $this->manila('21:00'),
        );

        $this->assertSame(
            '500.00',
            $moved->amount,
            'The moved slot must be charged at the rate that applies when it moves.',
        );
    }
}

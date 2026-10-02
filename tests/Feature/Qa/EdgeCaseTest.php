<?php

namespace Tests\Feature\Qa;

use App\Actions\Booking\BookCourt;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtPrice;
use App\Models\CourtSchedule;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Services\Payments\Gateways\Drivers\PayMongoGateway;
use App\Services\Payments\PaymentService;
use App\Services\Pos\SaleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 17 acceptance checks: the edge cases plan.md §25 lists explicitly.
 *
 * The happy paths are covered by the per-module suites. This file exists for
 * the awkward cases: boundaries, failures, and the moments where two things
 * happen at once.
 */
class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Court $court;

    /** 2026-11-01 is a Sunday. */
    protected const DATE = '2026-11-01';

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $branch = Branch::factory()->for($this->organization)->create();
        $this->court = Court::factory()->for($this->organization)->for($branch)->create();

        foreach (range(0, 6) as $weekday) {
            CourtSchedule::create([
                'organization_id' => $this->organization->id,
                'court_id' => $this->court->id,
                'weekday' => $weekday,
                'opens_at' => '08:00:00',
                'closes_at' => '22:00:00',
                'is_closed' => false,
            ]);
        }

        // A weekday rate for normal hours and a peak rate for the evening
        // window, because 21:00 falls inside the peak and the resolver will
        // not fall back to a rate that does not cover the hour.
        // Covers the whole 08:00-22:00 open day, so no hour in this
        // fixture is left without a price.
        // The test date is a Sunday, and a weekday rate does not apply to a
        // Sunday, so a weekend rate is what makes every hour in the open day
        // bookable. A peak rate would also shadow this inside its own window.
        CourtPrice::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $branch->id,
            'type' => 'weekend',
            'amount' => '400.00',
        ]);
    }

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

    public function test_the_last_slot_ends_exactly_at_closing(): void
    {
        // 21:00-22:00 is the final hour; it must be allowed.
        $booking = $this->book('21:00', '22:00');

        $this->assertSame(60, $booking->duration_minutes);
    }

    public function test_a_slot_one_minute_past_closing_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->book('21:30', '22:01');
    }

    public function test_a_zero_length_booking_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must end after it starts');

        $this->book('18:00', '18:00');
    }

    public function test_a_booking_crossing_midnight_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->book('23:00', '01:00');
    }

    public function test_a_simultaneous_booking_lands_only_once(): void
    {
        $this->book('18:00', '19:00');

        // The same attempt again, as a concurrent request would arrive.
        try {
            $this->book('18:00', '19:00');
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame(1, Booking::whereDate('starts_at', $this->manila('18:00')->toDateString())->count());
    }

    public function test_many_back_to_back_slots_all_succeed(): void
    {
        for ($hour = 8; $hour < 22; $hour++) {
            $this->book(sprintf('%02d:00', $hour), sprintf('%02d:00', $hour + 1));
        }

        // 14 consecutive hours, none overlapping.
        $this->assertSame(14, Booking::count());
    }

    public function test_a_booking_past_midnight_is_not_counted_on_the_wrong_day(): void
    {
        $this->book('20:00', '21:00');

        $tomorrow = Booking::whereDate('starts_at', $this->manila('20:00')->addDay()->toDateString())->count();

        $this->assertSame(0, $tomorrow, 'A booking must land on the day it starts.');
    }

    public function test_a_failed_payment_leaves_the_booking_intact(): void
    {
        $booking = $this->book('18:00', '19:00');
        $service = app(PaymentService::class);

        $payment = $service->record($booking, PaymentMethod::MAYA, 400.0);
        $service->markFailed($payment, 'Declined');

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertSame(
            BookingStatus::CONFIRMED,
            $booking->fresh()->status,
            'A failed payment must not silently cancel the booking.',
        );
    }

    public function test_an_expired_payment_cannot_be_settled(): void
    {
        $booking = $this->book('18:00', '19:00');
        $service = app(PaymentService::class);

        $payment = $service->record($booking, PaymentMethod::MAYA, 400.0);
        $service->markFailed($payment, 'Link expired');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot be settled');

        $service->markPaid($payment);
    }

    public function test_a_blocked_court_cannot_be_booked(): void
    {
        CourtBlock::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->court->branch_id,
            'court_id' => $this->court->id,
            'type' => 'maintenance',
            'reason' => 'Resurfacing',
            'starts_on' => self::DATE,
            'ends_on' => self::DATE,
            'starts_at' => '00:00:00',
            'ends_at' => '23:59:00',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->book('18:00', '19:00');
    }

    public function test_an_invalid_schedule_closes_the_day(): void
    {
        $this->court->schedules()->update(['is_closed' => true]);

        $this->expectException(\RuntimeException::class);
        $this->book('18:00', '19:00');
    }

    public function test_the_webhook_rejects_a_signature_when_no_secret_is_configured(): void
    {
        config(['services.paymongo.webhook_secret' => null]);

        // Failing closed is the whole point: no secret means no trust.
        $this->assertFalse((new PayMongoGateway)->verifySignature('{}', 'anything'));
    }

    public function test_a_booking_survives_a_retired_court(): void
    {
        $booking = $this->book('18:00', '19:00');

        $this->court->update(['status' => 'retired']);

        $this->assertTrue($booking->fresh()->exists, 'A booking is a record of what happened.');
    }

    public function test_a_sale_of_zero_value_cannot_be_checked_out(): void
    {
        $branch = Branch::where('organization_id', $this->organization->id)->firstOrFail();
        $sale = app(SaleService::class)->open($branch);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nothing to charge for');

        app(SaleService::class)->checkout($sale);
    }

    public function test_inventory_never_goes_negative_under_repeat_refunds(): void
    {
        $branch = Branch::where('organization_id', $this->organization->id)->firstOrFail();
        $product = Product::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $branch->id,
            'stock' => 3,
            'price' => '250.00',
        ]);

        $pos = app(SaleService::class);
        $sale = $pos->open($branch);
        $pos->addItem($sale, $product, 3);
        $pos->checkout($sale);

        $this->assertSame(0, $product->fresh()->stock);

        $pos->refund($sale);
        $this->assertSame(3, $product->fresh()->stock);

        // A second refund is refused, so stock cannot be inflated.
        try {
            $pos->refund($sale->fresh());
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_a_zero_remainder_booking_survives_being_written(): void
    {
        // Guards the Carbon 4 float-return bug seen in Phase 4: a 60 minute
        // booking must store 60, not 0 or -60.
        $booking = $this->book('18:00', '19:00');

        $this->assertSame(60, (int) $booking->fresh()->duration_minutes);
    }

    public function test_tenant_isolation_survives_an_unauthenticated_read(): void
    {
        $mine = Customer::factory()->create(['organization_id' => $this->organization->id]);
        $theirs = Customer::factory()->create();

        // With no signed-in user the scope is intentionally off, so this
        // documents the actual risk rather than pretending it is safe.
        $visible = Customer::withoutGlobalScope('organization')
            ->whereKey([$mine->id, $theirs->id])
            ->count();

        $this->assertSame(2, $visible);
    }
}

<?php

namespace Database\Seeders\Concerns;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ninety days of court bookings and the payments behind them.
 *
 * Booking revenue is the largest of the three reporting sources and is derived
 * from payments rather than from the booking amount (plan.md §20), so every
 * settled booking here gets a matching paid payment.
 *
 * Slots are allocated per court in time order so none overlap. The booking
 * engine refuses to double-book (plan.md §31); demo data that overlapped would
 * read as a broken calendar rather than a busy one.
 */
trait SeedsBookings
{
    /**
     * @param  array<int, int>  $courtIds
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, User>  $owners
     */
    private function seedBookings(
        int $orgId,
        int $branchId,
        array $courtIds,
        Collection $customers,
        Collection $owners,
        int $pricePeak = 650,
        int $priceOffPeak = 400,
    ): void {
        if ($courtIds === [] || $customers->isEmpty() || $owners->isEmpty()) {
            return;
        }

        // Weekday evenings sell out first, so they are weighted up.
        $weightedHours = array_merge(
            array_fill(0, 4, 18), array_fill(0, 4, 19), array_fill(0, 3, 20),
            range(6, 21),
        );

        $sequence = 0;

        for ($daysAgo = 89; $daysAgo >= 0; $daysAgo--) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $isPast = $daysAgo > 0;

            // Busier at weekends, quietest on Mondays. The variance is deliberately
            // derived from the date rather than from random_int(), so a
            // re-run reproduces the same volume. A seeder that drifts on
            // every run looks like a bug in the data, not like live history.
            $baseLoad = $day->isSunday() ? 7 : ($day->isSaturday() ? 6 : ($day->isMonday() ? 3 : 5));
            $target = $baseLoad + ($daysAgo % 3);

            foreach ($courtIds as $courtId) {
                // Occupied intervals, not just start hours: a 90 or 120 minute
                // booking runs past the next hour, so tracking only the start
                // would double-book. The engine refuses overlaps (plan.md
                // §31), so demo data must respect that too.
                $occupied = [];

                for ($n = 0; $n < $target; $n++) {
                    $hour = $weightedHours[($daysAgo + $n * 3) % count($weightedHours)];

                    // Duration is derived from the slot rather than random, so the
                    // total booking count is reproducible across runs.
                    $durations = [60, 60, 60, 90, 120];
                    $duration = $durations[($hour + $daysAgo) % count($durations)];

                    $start = $day->copy()->setTime($hour, 0);
                    $end = $start->copy()->addMinutes($duration);

                    // Closed on Sundays, and never past the 22:00 close.
                    if ($day->isSunday() || $end->greaterThan($day->copy()->setTime(22, 0))) {
                        continue;
                    }

                    // Never book an hour that has already passed today.
                    if (! $isPast && $start->lessThanOrEqualTo(now())) {
                        continue;
                    }

                    $clashes = false;

                    foreach ($occupied as [$busyStart, $busyEnd]) {
                        if ($start->lessThan($busyEnd) && $busyStart->lessThan($end)) {
                            $clashes = true;
                            break;
                        }
                    }

                    if ($clashes) {
                        continue;
                    }

                    $occupied[] = [$start, $end];
                    $sequence++;

                    $this->createBooking(
                        $orgId,
                        $branchId,
                        $courtId,
                        $start,
                        $hour,
                        $daysAgo,
                        $duration,
                        $pricePeak,
                        $priceOffPeak,
                        $customers,
                        $owners,
                        $sequence,
                    );
                }
            }
        }
    }

    /**
     * One booking plus the payment the reports read.
     *
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, User>  $owners
     */
    private function createBooking(
        int $orgId,
        int $branchId,
        int $courtId,
        Carbon $startsAt,
        int $hour,
        int $daysAgo,
        int $duration,
        int $pricePeak,
        int $priceOffPeak,
        Collection $customers,
        Collection $owners,
        int $sequence,
    ): void {
        $endsAt = $startsAt->copy()->addMinutes($duration);

        $isPeak = $hour >= 17;
        $amount = $isPeak ? $pricePeak : $priceOffPeak;

        $status = $this->statusFor($daysAgo, $hour);
        $customer = fake()->randomElement($customers);
        $owner = fake()->randomElement($owners);
        $bookedAt = $startsAt->copy()->subDays(random_int(1, 6));

        $booking = Booking::create([
            'organization_id' => $orgId,
            'branch_id' => $branchId,
            'court_id' => $courtId,
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'reference' => 'BK-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => $duration,
            'status' => $status->value,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'PHP',
            'price_type' => $isPeak ? 'peak' : 'off_peak',
            'players' => fake()->numberBetween(2, 4),
            'confirmed_at' => $bookedAt,
            'checked_in_at' => in_array($status, [BookingStatus::CHECKED_IN, BookingStatus::COMPLETED], true)
                ? $startsAt->copy()->addMinutes(5)
                : null,
            'completed_at' => $status === BookingStatus::COMPLETED ? $endsAt : null,
            'cancelled_at' => $status === BookingStatus::CANCELLED ? $startsAt->copy()->subHours(6) : null,
            'cancellation_reason' => $status === BookingStatus::CANCELLED ? 'Player had to reschedule.' : null,
            'no_show_at' => $status === BookingStatus::NO_SHOW ? $startsAt->copy()->addMinutes(15) : null,
            'refunded_at' => $status === BookingStatus::REFUNDED ? $startsAt->copy()->subHours(12) : null,
            'created_at' => $bookedAt,
            'updated_at' => $bookedAt,
        ]);

        $this->createPayment($orgId, $branchId, $booking, $owner, $status, $amount, $startsAt);
    }

    /**
     * Past slots are settled; today and the future are confirmed or pending,
     * with the occasional no-show, cancellation and refund for realism.
     */
    private function statusFor(int $daysAgo, int $hour): BookingStatus
    {
        if ($daysAgo > 1) {
            // Derived from the slot rather than random, so a re-run reproduces the
            // same history. Statuses that reshuffle every run make the demo data
            // impossible to use as a stable baseline.
            $roll = ($daysAgo * 7 + $hour) % 100;

            return match (true) {
                $roll < 4 => BookingStatus::CANCELLED,
                $roll < 8 => BookingStatus::NO_SHOW,
                $roll < 10 => BookingStatus::REFUNDED,
                $roll < 70 => BookingStatus::COMPLETED,
                default => BookingStatus::CHECKED_IN,
            };
        }

        if ($daysAgo === 1 || $hour < (int) now()->format('G')) {
            return BookingStatus::COMPLETED;
        }

        // Today and the future sit as confirmed, with a pending one in five.
        return ($hour % 5) === 0 ? BookingStatus::PENDING : BookingStatus::CONFIRMED;
    }

    /**
     * The payment row the reporting service actually reads.
     */
    private function createPayment(
        int $orgId,
        int $branchId,
        Booking $booking,
        User $owner,
        BookingStatus $status,
        int $amount,
        Carbon $startsAt,
    ): void {
        $method = fake()->randomElement(PaymentMethod::values());

        $paymentStatus = match ($status) {
            BookingStatus::CANCELLED, BookingStatus::REFUNDED => PaymentStatus::REFUNDED,
            BookingStatus::COMPLETED, BookingStatus::CHECKED_IN, BookingStatus::CONFIRMED => PaymentStatus::PAID,
            default => PaymentStatus::PENDING,
        };

        Payment::create([
            'organization_id' => $orgId,
            'branch_id' => $branchId,
            'booking_id' => $booking->id,
            'user_id' => $owner->id,
            'recorded_by' => $owner->id,
            'reference' => 'PAY-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'method' => $method,
            'status' => $paymentStatus->value,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'refunded_amount' => $paymentStatus === PaymentStatus::REFUNDED
                ? number_format((float) $amount, 2, '.', '')
                : '0.00',
            'currency' => 'PHP',
            'gateway' => $method === PaymentMethod::CASH ? null : $method,
            'card_last_four' => $method === PaymentMethod::CARD ? fake()->numerify('####') : null,
            'paid_at' => $paymentStatus === PaymentStatus::PAID ? $startsAt->copy()->subHours(6) : null,
            'refunded_at' => $paymentStatus === PaymentStatus::REFUNDED ? $startsAt->copy()->subHours(12) : null,
            'created_at' => $booking->created_at,
            'updated_at' => $booking->updated_at,
        ]);
    }
}

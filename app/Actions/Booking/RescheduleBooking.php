<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PriceResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Moves an existing booking to a new time on the same court (plan.md §12).
 *
 * Both the booking and its court are locked for the whole transaction, so the
 * new slot cannot be taken between the availability check and the write. The
 * booking being moved is excluded from its own check, otherwise it would always
 * appear to clash with itself.
 */
class RescheduleBooking
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PriceResolver $prices,
    ) {}

    /**
     * @throws RuntimeException when the new window is not available
     */
    public function handle(
        Booking $booking,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?User $actor = null,
    ): Booking {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new RuntimeException('A booking must end after it starts.');
        }

        return DB::transaction(function () use ($booking, $startsAt, $endsAt, $actor): Booking {
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $lockedBooking->status->isCancellable()) {
                throw new RuntimeException(
                    "A {$lockedBooking->status->label()} booking can no longer be rescheduled."
                );
            }

            $court = $lockedBooking->court()->lockForUpdate()->firstOrFail();

            if (! $this->availability->isAvailable($court, $startsAt, $endsAt, $lockedBooking->id)) {
                throw new RuntimeException('That time slot is no longer available.');
            }

            $localStart = $startsAt->copy()->setTimezone(config('platform.locale.timezone'));
            $amount = $this->prices->amountFor($court, $localStart, (int) $localStart->format('G'));

            if ($amount === null) {
                throw new RuntimeException('This facility has not published a price for that time.');
            }

            $lockedBooking->starts_at = $this->toCarbon($startsAt);
            $lockedBooking->ends_at = $this->toCarbon($endsAt);
            $lockedBooking->duration_minutes = (int) abs($endsAt->diffInMinutes($startsAt));
            $lockedBooking->amount = $amount;
            $lockedBooking->save();

            $lockedBooking->statusHistory()->create([
                'user_id' => $actor?->id,
                'from_status' => $lockedBooking->status,
                'to_status' => $lockedBooking->status,
                'note' => 'Rescheduled to '.$localStart->format('M j, Y g:i A'),
            ]);

            return $lockedBooking;
        }, 3);
    }

    /**
     * Normalise any accepted time input to a mutable Carbon instance.
     */
    protected function toCarbon(mixed $value): SupportCarbon
    {
        return SupportCarbon::instance(
            $value instanceof \DateTimeInterface ? $value : SupportCarbon::parse($value)
        );
    }
}

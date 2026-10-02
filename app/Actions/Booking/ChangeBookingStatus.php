<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Status transitions that release a booked slot (plan.md §12).
 *
 * Every transition runs in a transaction that locks the booking row first, so
 * two staff members acting on the same booking at once cannot both apply a
 * transition — the loser sees the already-changed status and is rejected.
 */
class ChangeBookingStatus
{
    /**
     * Move a booking to a new status, recording who did it and why.
     */
    public function handle(Booking $booking, BookingStatus $to, ?User $actor = null, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($booking, $to, $actor, $note): Booking {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === $to) {
                throw new RuntimeException("This booking is already {$to->label()}.");
            }

            if ($locked->status->isTerminal()) {
                throw new RuntimeException(
                    "A {$locked->status->label()} booking can no longer be changed."
                );
            }

            $from = $locked->status;

            $locked->status = $to;
            $this->stamp($locked, $to);

            $locked->save();

            $locked->statusHistory()->create([
                'user_id' => $actor?->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);

            return $locked;
        }, 3);
    }

    /**
     * Cancel and optionally record a reason (plan.md §12).
     */
    public function cancel(Booking $booking, ?User $actor = null, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isCancellable()) {
                throw new RuntimeException(
                    "A {$locked->status->label()} booking can no longer be cancelled."
                );
            }

            $from = $locked->status;

            $locked->status = BookingStatus::CANCELLED;
            $locked->cancelled_at = now();
            $locked->cancelled_by = $actor?->id;
            $locked->cancellation_reason = $reason;
            $locked->save();

            $locked->statusHistory()->create([
                'user_id' => $actor?->id,
                'from_status' => $from,
                'to_status' => BookingStatus::CANCELLED,
                'note' => $reason,
            ]);

            return $locked;
        }, 3);
    }

    /**
     * Keep the lifecycle timestamps in step with the status.
     */
    protected function stamp(Booking $booking, BookingStatus $to): void
    {
        match ($to) {
            BookingStatus::CONFIRMED => $booking->confirmed_at = now(),
            BookingStatus::CHECKED_IN => $booking->checked_in_at = now(),
            BookingStatus::COMPLETED => $booking->completed_at = now(),
            BookingStatus::NO_SHOW => $booking->no_show_at = now(),
            BookingStatus::REFUNDED => $booking->refunded_at = now(),
            default => null,
        };
    }
}

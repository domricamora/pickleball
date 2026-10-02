<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Services\AvailabilityService;
use App\Services\PriceResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates a booking, refusing to double-book a court.
 *
 * The overlap check and the insert run inside one transaction that first takes
 * a row lock on the court. Two concurrent requests for the same court are
 * therefore serialised on that lock: the second waits, and once it proceeds it
 * re-reads the court and sees the first booking, so it is rejected rather than
 * silently overwriting (plan.md §31).
 *
 * A unique index cannot express "no two intervals may intersect", so
 * correctness depends on this lock together with the interval query.
 */
class BookCourt
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PriceResolver $prices,
    ) {}

    /**
     * @param  array{starts_at: CarbonInterface, ends_at: CarbonInterface, user_id?: int|null, customer_id?: int|null, notes?: string|null, players?: int|null, status?: BookingStatus|null}  $data
     *
     * @throws RuntimeException when the slot is not actually available
     */
    public function handle(Court $court, array $data): Booking
    {
        $startsAt = $this->toCarbon($data['starts_at']);
        $endsAt = $this->toCarbon($data['ends_at']);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new RuntimeException('A booking must end after it starts.');
        }

        return DB::transaction(function () use ($court, $data, $startsAt, $endsAt): Booking {
            // Serialise every booking attempt for this court.
            $locked = Court::query()->whereKey($court->id)->lockForUpdate()->firstOrFail();

            if (! $this->availability->isAvailable($locked, $startsAt, $endsAt)) {
                throw new RuntimeException('That time slot is no longer available.');
            }

            $localStart = $startsAt->copy()->setTimezone(config('platform.locale.timezone'));
            $amount = $this->prices->amountFor($locked, $localStart, (int) $localStart->format('G'));

            if ($amount === null) {
                // Never guess a price; the facility must publish one first.
                throw new RuntimeException('This facility has not published a price for that time.');
            }

            $status = $data['status'] ?? BookingStatus::CONFIRMED;

            $booking = Booking::create([
                'organization_id' => $locked->organization_id,
                'branch_id' => $locked->branch_id,
                'court_id' => $locked->id,
                'user_id' => $data['user_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => abs($endsAt->diffInMinutes($startsAt)),
                'status' => $status,
                'amount' => $amount,
                'currency' => 'PHP',
                'players' => $data['players'] ?? $locked->capacity,
                'notes' => $data['notes'] ?? null,
                'confirmed_at' => $status === BookingStatus::CONFIRMED ? now() : null,
            ]);

            $booking->statusHistory()->create([
                'user_id' => $data['user_id'] ?? null,
                'from_status' => null,
                'to_status' => $status,
                'note' => 'Booking created',
            ]);

            return $booking;
        }, 3);
    }

    /**
     * Normalise any accepted time input to a mutable Carbon instance.
     */
    protected function toCarbon(mixed $value): Carbon
    {
        return $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);
    }
}

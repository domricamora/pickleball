<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtBlock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Works out which slots a court can actually be booked for (plan.md §31).
 *
 * Availability is the intersection of:
 *   1. the court's weekly schedule for that weekday
 *   2. court status (only "available" is bookable)
 *   3. maintenance / holiday / event blocks
 *   4. existing bookings that still occupy the slot
 *
 * Times are handled in Asia/Manila and stored in UTC.
 */
class AvailabilityService
{
    /**
     * Build the bookable slots for a court on a date.
     *
     * @return array<int, array{starts_at: CarbonImmutable, ends_at: CarbonImmutable, duration_minutes: int, amount: float|null}>
     */
    public function slotsFor(Court $court, CarbonInterface $date, int $incrementMinutes = 60): array
    {
        if (! $court->isBookable()) {
            return [];
        }

        $local = CarbonImmutable::parse($date->format('Y-m-d'), config('platform.locale.timezone'));
        $weekday = (int) $local->dayOfWeek;

        $schedule = $court->schedules()->where('weekday', $weekday)->first();

        if ($schedule === null || $schedule->is_closed) {
            return [];
        }

        $opensAt = $this->atTime($local, $schedule->opens_at);
        $closesAt = $this->atTime($local, $schedule->closes_at);

        if (! $opensAt || ! $closesAt) {
            return [];
        }

        $blocked = $this->blocksFor($court, $local);
        $existing = $this->bookingsFor($court, $local);

        $resolver = app(PriceResolver::class);
        $slots = [];

        for ($start = $opensAt; $start->lt($closesAt); $start = $start->addMinutes($incrementMinutes)) {
            $end = $start->addMinutes($incrementMinutes);

            if (! $end->lte($closesAt)) {
                break;
            }

            if ($this->hitsBlock($blocked, $start, $end)) {
                continue;
            }

            if ($this->hitsBooking($existing, $start, $end)) {
                continue;
            }

            $slots[] = [
                'starts_at' => $start,
                'ends_at' => $end,
                'duration_minutes' => $incrementMinutes,
                'amount' => $resolver->amountFor($court, $start, (int) $start->format('G')),
            ];
        }

        return $slots;
    }

    /**
     * Whether the court is free for the whole window.
     *
     * Callers must already hold the court lock; see BookCourt.
     */
    public function isAvailable(Court $court, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool
    {
        if (! $court->isBookable()) {
            return false;
        }

        $localStart = CarbonImmutable::parse($start)->setTimezone(config('platform.locale.timezone'));
        $localEnd = CarbonImmutable::parse($end)->setTimezone(config('platform.locale.timezone'));

        $schedule = $court->schedules()->where('weekday', (int) $localStart->dayOfWeek)->first();

        if ($schedule === null || $schedule->is_closed) {
            return false;
        }

        // The whole window must sit inside the court's opening hours.
        if (! $this->atTime($localStart->startOfDay(), $schedule->opens_at)?->lte($localStart)) {
            return false;
        }

        if (! $this->atTime($localStart->startOfDay(), $schedule->closes_at)?->gte($localEnd)) {
            return false;
        }

        if ($this->hitsBlock($this->blocksFor($court, $localStart), $localStart, $localEnd)) {
            return false;
        }

        return ! $this->hitsBooking(
            $this->bookingsFor($court, $localStart, $ignoreBookingId),
            $localStart,
            $localEnd,
        );
    }

    /**
     * Blocks that overlap the local date, either court-specific or whole-branch.
     *
     * @return Collection<int, CourtBlock>
     */
    protected function blocksFor(Court $court, CarbonInterface $local): Collection
    {
        return CourtBlock::query()
            ->where(function ($query) use ($court): void {
                $query->whereNull('court_id')->orWhere('court_id', $court->id);
            })
            ->where(function ($query) use ($court): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $court->branch_id);
            })
            ->whereDate('starts_on', '<=', $local->toDateString())
            ->whereDate('ends_on', '>=', $local->toDateString())
            ->get();
    }

    /**
     * Existing bookings that still occupy the court on that date.
     *
     * @param  int|null  $ignoreBookingId  Excluded so a booking can be
     *                                     rescheduled without colliding with
     *                                     its own current slot.
     * @return Collection<int, Booking>
     */
    protected function bookingsFor(Court $court, CarbonInterface $local, ?int $ignoreBookingId = null): Collection
    {
        return Booking::query()
            ->where('court_id', $court->id)
            ->whereIn('status', array_map(
                fn (BookingStatus $status): string => $status->value,
                BookingStatus::occupying(),
            ))
            ->where('starts_at', '<', $local->addDay()->setTime(23, 59))
            ->where('ends_at', '>', $local->setTime(0, 0))
            ->when($ignoreBookingId !== null, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->get();
    }

    /**
     * @param  Collection<int, CourtBlock>  $blocks
     */
    protected function hitsBlock(Collection $blocks, CarbonInterface $start, CarbonInterface $end): bool
    {
        foreach ($blocks as $block) {
            // An all-day block covers anything on those dates.
            if ($block->starts_at === null || $block->ends_at === null) {
                $day = $start->toDateString();

                if ($day >= $block->starts_on->toDateString() && $day <= $block->ends_on->toDateString()) {
                    return true;
                }

                continue;
            }

            $blockStart = $this->atTime($start->startOfDay(), $block->starts_at);
            $blockEnd = $this->atTime($start->startOfDay(), $block->ends_at);

            if ($blockStart && $blockEnd && $start->lt($blockEnd) && $end->gt($blockStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    protected function hitsBooking(Collection $bookings, CarbonInterface $start, CarbonInterface $end): bool
    {
        foreach ($bookings as $booking) {
            // Half-open comparison: a booking ending exactly at $start is fine.
            if ($booking->starts_at->lt($end) && $booking->ends_at->gt($start)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a Manila-local datetime from a "H:i:s" clock time.
     */
    protected function atTime(CarbonInterface $day, string $time): ?CarbonImmutable
    {
        $time = substr($time, 0, 5);

        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return CarbonImmutable::instance($day->setTime($hour, $minute));
    }
}

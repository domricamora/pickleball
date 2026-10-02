<?php

namespace App\Services\Customers;

use App\Enums\BookingStatus;
use App\Enums\CustomerSegment;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lifetime metrics and segmentation for a player (plan.md §14).
 *
 * Thresholds are configuration, not magic numbers, so a facility can tune
 * what "frequent" or "inactive" means without touching this class.
 */
class CustomerSegmenter
{
    /** A customer with no history at all. */
    public const NEW_PLAYER_MAX_BOOKINGS = 1;

    /** Bookings in this window make someone "active". */
    public const ACTIVE_WINDOW_DAYS = 90;

    /** No booking in this long makes someone "inactive". */
    public const INACTIVE_WINDOW_DAYS = 180;

    /** Bookings in this window to count as a frequent renter. */
    public const FREQUENT_RENTER_WINDOW_DAYS = 90;

    public const FREQUENT_RENTER_MIN_BOOKINGS = 6;

    /**
     * Bookings that actually happened: a cancelled slot is not a visit.
     */
    protected const COUNTED_STATUSES = [
        BookingStatus::CHECKED_IN,
        BookingStatus::COMPLETED,
        BookingStatus::NO_SHOW,
    ];

    /**
     * The dashboard figures for one customer.
     *
     * @return array<string, mixed>
     */
    public function metricsFor(Customer $customer): array
    {
        $bookings = $customer->bookings()
            ->whereIn('status', array_map(
                fn (BookingStatus $status): string => $status->value,
                self::COUNTED_STATUSES,
            ))
            ->get();

        $lastVisit = $bookings->max('starts_at');

        return [
            'total_bookings' => $bookings->count(),
            'total_spending' => $this->spendingFor($customer),
            'last_visit' => $lastVisit?->timezone(config('platform.locale.timezone')),
            'no_shows' => $bookings->where('status', BookingStatus::NO_SHOW)->count(),
            'cancelled' => $customer->bookings()
                ->where('status', BookingStatus::CANCELLED->value)
                ->count(),
            'favorite_branch' => $this->favoriteOf($bookings, 'branch_id'),
            'favorite_court' => $this->favoriteOf($bookings, 'court_id'),
            'membership_status' => 'none', // Phase 7 owns memberships.
        ];
    }

    /**
     * Pesos actually collected from this customer, net of refunds.
     */
    public function spendingFor(Customer $customer): float
    {
        return round((float) $customer->bookings()
            ->join('payments', 'payments.booking_id', '=', 'bookings.id')
            ->whereIn('payments.status', [
                PaymentStatus::PAID->value,
                PaymentStatus::PARTIALLY_REFUNDED->value,
                PaymentStatus::REFUNDED->value,
            ])
            ->sum(DB::raw('payments.amount - payments.refunded_amount')), 2);
    }

    /**
     * Which segments this customer belongs to.
     *
     * A customer can be in several at once: a VIP is also a member and a
     * frequent renter, and that is more useful than picking one.
     *
     * @return Collection<int, CustomerSegment>
     */
    public function segmentsFor(Customer $customer): Collection
    {
        $metrics = $this->metricsFor($customer);
        $segments = collect();

        $total = (int) $metrics['total_bookings'];
        $lastVisit = $metrics['last_visit'];

        if ($total <= self::NEW_PLAYER_MAX_BOOKINGS && $lastVisit === null) {
            $segments->push(CustomerSegment::NEW_PLAYER);
        }

        if ($lastVisit !== null) {
            $daysSinceVisit = $lastVisit->diffInDays(now());

            if ($daysSinceVisit <= self::ACTIVE_WINDOW_DAYS) {
                $segments->push(CustomerSegment::ACTIVE);
            }

            if ($daysSinceVisit > self::INACTIVE_WINDOW_DAYS) {
                $segments->push(CustomerSegment::INACTIVE);
            }
        }

        if ($customer->is_vip) {
            $segments->push(CustomerSegment::VIP);
        }

        $recent = $customer->bookings()
            ->whereIn('status', [
                BookingStatus::CHECKED_IN->value,
                BookingStatus::COMPLETED->value,
            ])
            ->where('starts_at', '>=', now()->subDays(self::FREQUENT_RENTER_WINDOW_DAYS))
            ->count();

        if ($recent >= self::FREQUENT_RENTER_MIN_BOOKINGS) {
            $segments->push(CustomerSegment::FREQUENT_RENTER);
        }

        // Membership and tournament segments arrive with Phases 7 and 8.
        return $segments->unique()->values();
    }

    /**
     * The most-visited branch or court, by name.
     */
    protected function favoriteOf(Collection $bookings, string $foreignKey): ?string
    {
        $counts = $bookings
            ->groupBy($foreignKey)
            ->map->count()
            ->filter(fn (int $count, $id): bool => $id > 0 && $count > 0)
            ->sortDesc();

        $id = $counts->keys()->first();

        if ($id === null) {
            return null;
        }

        $model = $foreignKey === 'branch_id'
            ? Branch::find($id)
            : Court::find($id);

        return $model?->name;
    }
}

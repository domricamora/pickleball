<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Booking authorisation (plan.md §7, §10).
 *
 * A booking belongs to the court it reserves and therefore to the tenant that
 * owns that court. The BelongsToOrganization scope already prevents a row from
 * another tenant being loaded; these checks stop the action itself.
 */
class BookingPolicy
{
    /**
     * Platform staff may act on any booking.
     */
    public function before(User $user): ?bool
    {
        return $user->isPlatformStaff() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->belongsToTenant($user, $booking);
    }

    /**
     * Front desk staff manage the bookings they take at the counter.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $this->belongsToTenant($user, $booking) && $user->can('bookings.manage');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->belongsToTenant($user, $booking) && $user->can('bookings.manage');
    }

    /**
     * A booking is only ever reachable from inside its own tenant.
     */
    protected function belongsToTenant(User $user, Booking $booking): bool
    {
        return $user->organization_id !== null
            && $booking->organization_id === $user->organization_id;
    }
}

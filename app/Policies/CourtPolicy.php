<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

/**
 * Court authorisation (plan.md §7, §11).
 */
class CourtPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isPlatformStaff() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Court $court): bool
    {
        return $this->belongsToTenant($user, $court);
    }

    public function create(User $user): bool
    {
        return $user->can('courts.manage');
    }

    public function update(User $user, Court $court): bool
    {
        return $this->belongsToTenant($user, $court) && $user->can('courts.manage');
    }

    public function delete(User $user, Court $court): bool
    {
        return $this->belongsToTenant($user, $court) && $user->can('courts.manage');
    }

    /**
     * Managing schedules and blocks rides with the court itself.
     */
    public function manageSchedule(User $user, Court $court): bool
    {
        return $this->belongsToTenant($user, $court) && $user->can('courts.manage');
    }

    public function managePricing(User $user, Court $court): bool
    {
        return $this->belongsToTenant($user, $court) && $user->can('courts.manage');
    }

    protected function belongsToTenant(User $user, Court $court): bool
    {
        return $user->organization_id !== null
            && $court->organization_id === $user->organization_id;
    }
}

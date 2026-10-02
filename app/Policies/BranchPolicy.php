<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * Facility authorisation (plan.md §7, §10).
 *
 * Authorisation is always enforced server-side. Hiding a button in the UI is
 * a convenience, never the control.
 */
class BranchPolicy
{
    /**
     * Platform staff may act on any facility.
     */
    public function before(User $user): ?bool
    {
        return $user->isPlatformStaff() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->belongsToTenant($user, $branch);
    }

    public function create(User $user): bool
    {
        return $user->can('facilities.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $this->belongsToTenant($user, $branch) && $user->can('facilities.manage');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $this->belongsToTenant($user, $branch)
            && $user->can('facilities.manage')
            && ! $branch->is_primary;
    }

    /**
     * A facility is only ever reachable from inside its own tenant.
     */
    protected function belongsToTenant(User $user, Branch $branch): bool
    {
        return $user->organization_id !== null
            && $branch->organization_id === $user->organization_id;
    }
}

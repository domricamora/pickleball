<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant isolation (plan.md §6, §28).
 *
 * Applying this to a model means every query is automatically restricted to
 * the signed-in user's organization, so a forgotten `where` clause can never
 * leak another facility's rows.
 *
 * Platform staff (Super Admin) deliberately bypass the scope so they can
 * administer tenants — every such action is still authorised explicitly in a
 * Policy, never assumed.
 */
trait BelongsToOrganization
{
    /**
     * Apply the scope when the model already belongs to a tenant.
     */
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $user = Auth::user();

            /*
             * No authenticated user: artisan commands, seeders, the installer
             * and queued jobs run unscoped by design. Genuine cross-tenant
             * work in those contexts must ask for it explicitly with
             * `withoutGlobalScope('organization')`.
             */
            if ($user === null) {
                return;
            }

            // Platform staff administer every tenant (plan.md §7).
            if ($user->isPlatformStaff()) {
                return;
            }

            $table = $builder->getModel()->getTable();

            /*
             * Signed in, but belonging to no tenant — a player account. It
             * must see no tenant data whatsoever. Leaving the scope off here
             * would expose every facility on the platform, so the condition is
             * forced to be unsatisfiable rather than skipped.
             */
            if ($user->organization_id === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($table.'.organization_id', $user->organization_id);
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The tenant the current request is scoped to, if any.
     */
    public static function currentOrganizationId(): ?int
    {
        return Auth::user()?->organization_id;
    }

    /**
     * Whether the current user may read across every tenant.
     *
     * Only platform staff. Use this to guard an explicit
     * `withoutGlobalScope('organization')` call so the intent is auditable.
     */
    public static function bypassesTenantScope(): bool
    {
        return Auth::user()?->isPlatformStaff() ?? false;
    }
}

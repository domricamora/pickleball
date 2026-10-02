<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MembershipSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A player's live membership (plan.md §15).
 *
 * `credits_remaining` is a cached balance for speed; the authoritative
 * history is membership_credit_usages. Both are written together under a row
 * lock so they cannot drift apart.
 *
 * @property SubscriptionStatus $status
 */
#[Fillable([
    'organization_id',
    'membership_plan_id',
    'customer_id',
    'user_id',
    'status',
    'starts_on',
    'ends_on',
    'cancelled_at',
    'credits_granted',
    'credits_remaining',
    'is_unlimited',
])]
class MembershipSubscription extends Model
{
    /** @use HasFactory<MembershipSubscriptionFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return BelongsTo<MembershipPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<MembershipCreditUsage, $this>
     */
    public function creditUsages(): HasMany
    {
        return $this->hasMany(MembershipCreditUsage::class);
    }

    /**
     * @param  Builder<MembershipSubscription>  $query
     * @return Builder<MembershipSubscription>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereDate('starts_on', '<=', now())
            ->whereDate('ends_on', '>=', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'cancelled_at' => 'date',
            'credits_granted' => 'integer',
            'credits_remaining' => 'integer',
            'is_unlimited' => 'boolean',
        ];
    }

    /**
     * Whether this membership can be spent against right now.
     */
    public function isUsable(): bool
    {
        if (! $this->status->canUseCredits()) {
            return false;
        }

        // Compare as dates so a time-of-day component cannot skew the result.
        $today = Carbon::now()->startOfDay();

        /** @phpstan-ignore-next-line the date cast is not visible statically. */
        return $this->starts_on->lte($today) && $this->ends_on->gte($today);
    }

    /**
     * Whether there is credit left to spend.
     */
    public function hasCredits(int $needed = 1): bool
    {
        if (! $this->isUsable()) {
            return false;
        }

        return $this->is_unlimited || $this->credits_remaining >= $needed;
    }
}

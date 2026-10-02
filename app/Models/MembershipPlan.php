<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recurring membership tier (plan.md §15).
 *
 * @property BillingCycle $billing_cycle
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'name',
    'description',
    'billing_cycle',
    'custom_days',
    'price',
    'currency',
    'sessions_included',
    'priority_booking',
    'member_only_events',
    'guest_passes',
    'discount_percent',
    'benefits',
    'is_active',
])]
class MembershipPlan extends Model
{
    use BelongsToOrganization, HasFactory;

    /**
     * @return HasMany<MembershipSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    /**
     * @return HasMany<MembershipPackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(MembershipPackage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'sessions_included' => 'integer',
            'guest_passes' => 'integer',
            'priority_booking' => 'boolean',
            'member_only_events' => 'boolean',
            'benefits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether this plan grants open-ended play.
     */
    public function isUnlimited(): bool
    {
        return $this->sessions_included === null;
    }

    public function formattedPrice(): string
    {
        return $this->price === null ? '—' : Money::format($this->price);
    }

    /**
     * Apply the member discount to a peso amount.
     */
    public function discount(float $amount): float
    {
        $percent = (float) $this->discount_percent;

        return round($amount * (1 - $percent / 100), 2);
    }
}

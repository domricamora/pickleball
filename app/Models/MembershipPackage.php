<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A prepaid pack of sessions (plan.md §15).
 */
#[Fillable([
    'organization_id',
    'membership_plan_id',
    'name',
    'sessions',
    'price',
    'currency',
    'valid_days',
    'is_active',
])]
class MembershipPackage extends Model
{
    use BelongsToOrganization, HasFactory;

    /**
     * @return BelongsTo<MembershipPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sessions' => 'integer',
            'valid_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable credit spend against a membership (plan.md §15).
 *
 * Credits are never edited or deleted; a correction is a new row. That keeps
 * the history trustworthy and prevents a lost update from minting credit.
 */
#[Fillable(['membership_subscription_id', 'booking_id', 'credits', 'used_at'])]
class MembershipCreditUsage extends Model
{
    /**
     * @return BelongsTo<MembershipSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MembershipSubscription::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'used_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A refund issued against a payment (plan.md §13).
 *
 * Reached through its payment, so it carries no organization_id of its own.
 */
#[Fillable([
    'payment_id',
    'user_id',
    'processed_by',
    'reference',
    'amount',
    'currency',
    'gateway_refund_id',
    'reason',
])]
class PaymentRefund extends Model
{
    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $refund): void {
            $refund->reference ??= 'REF-'.strtoupper(Str::random(8));
            $refund->currency ??= 'PHP';
        });
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }
}

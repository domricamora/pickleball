<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A customer receipt in Philippine peso (plan.md §13).
 *
 * Line items are snapshotted at issue time so a later price change cannot
 * rewrite what a customer was actually charged.
 */
#[Fillable([
    'organization_id',
    'payment_id',
    'booking_id',
    'number',
    'subtotal',
    'discount',
    'tax',
    'total',
    'currency',
    'line_items',
    'issued_at',
])]
class Receipt extends Model
{
    use BelongsToOrganization, HasFactory;

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'line_items' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $receipt): void {
            $receipt->number ??= 'RCPT-'.now()->format('Y').'-'.strtoupper(Str::random(6));
            $receipt->currency ??= 'PHP';
            $receipt->issued_at ??= now();
        });
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total);
    }
}

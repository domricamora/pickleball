<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A payment against a booking (plan.md §13).
 *
 * Amounts are pesos. Card data is never stored: only the gateway's opaque
 * payment id and the last four digits, which is all a receipt needs.
 *
 * @property PaymentStatus $status
 * @property PaymentMethod $method
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'booking_id',
    'user_id',
    'recorded_by',
    'reference',
    'method',
    'status',
    'amount',
    'refunded_amount',
    'currency',
    'gateway',
    'gateway_payment_id',
    'gateway_reference',
    'card_last_four',
    'failure_reason',
    'paid_at',
    'failed_at',
    'refunded_at',
    'notes',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<PaymentRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            $payment->reference ??= self::generateReference();
            $payment->currency ??= 'PHP';
        });
    }

    /**
     * A customer-facing reference, e.g. PAY-7K3M2Q9X.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'PAY-'.strtoupper(Str::random(8));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PaymentStatus::PAID->value,
            PaymentStatus::PARTIALLY_REFUNDED->value,
            PaymentStatus::REFUNDED->value,
        ]);
    }

    /**
     * Pesos still refundable on this payment.
     */
    public function refundableAmount(): float
    {
        return round((float) $this->amount - (float) $this->refunded_amount, 2);
    }

    public function isFullyRefunded(): bool
    {
        return (float) $this->refunded_amount >= (float) $this->amount;
    }

    /**
     * The amount as Philippine peso, e.g. ₱1,250.00.
     */
    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }

    /**
     * A payment is never described by raw card data.
     */
    public function methodLabel(): string
    {
        return $this->method->label().($this->card_last_four ? ' •••• '.$this->card_last_four : '');
    }
}

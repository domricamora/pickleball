<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A point-of-sale sale (plan.md §17).
 *
 * Money columns are always recomputed from the line items by SaleService, so
 * a hand-edited total can never disagree with the cart.
 *
 * @property SaleStatus $status
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'customer_id',
    'user_id',
    'booking_id',
    'payment_id',
    'processed_by',
    'reference',
    'status',
    'subtotal',
    'discount',
    'tax',
    'total',
    'amount_tendered',
    'change_due',
    'currency',
    'paid_at',
    'refunded_at',
    'notes',
])]
class Sale extends Model
{
    use BelongsToOrganization;

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::OPEN->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sale): void {
            $sale->reference ??= self::generateReference();
            $sale->currency ??= 'PHP';
        });
    }

    /**
     * A till reference such as POS-2026-7K3M2Q9X.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'POS-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total);
    }

    public function itemCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }
}

<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * An order placed with a supplier (plan.md §18).
 *
 * @property PurchaseOrderStatus $status
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'supplier_id',
    'created_by',
    'received_by',
    'reference',
    'status',
    'subtotal',
    'currency',
    'notes',
    'ordered_at',
    'received_at',
])]
class PurchaseOrder extends Model
{
    use BelongsToOrganization;

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'subtotal' => 'decimal:2',
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->reference ??= self::generateReference();
            $order->currency ??= 'PHP';
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'PO-'.now()->format('Y').'-'.strtoupper(Str::random(5));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal);
    }

    /**
     * Recompute the order total from its lines.
     */
    public function recalculate(): self
    {
        $this->subtotal = Money::toFloat(
            $this->items()->sum(DB::raw('unit_cost * quantity_ordered'))
        );

        $this->save();

        return $this;
    }
}

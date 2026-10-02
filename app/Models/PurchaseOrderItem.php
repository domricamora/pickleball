<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line on a purchase order.
 *
 * The name is snapshotted, and cost is the cost at the time of ordering, so a
 * later price change cannot rewrite what the facility agreed to pay.
 */
#[Fillable([
    'purchase_order_id',
    'product_id',
    'product_name',
    'unit_cost',
    'quantity_ordered',
    'quantity_received',
])]
class PurchaseOrderItem extends Model
{
    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
        ];
    }

    /**
     * How much is still outstanding.
     */
    public function outstanding(): int
    {
        return max(0, (int) $this->quantity_ordered - (int) $this->quantity_received);
    }

    public function isFullyReceived(): bool
    {
        return $this->outstanding() === 0;
    }
}

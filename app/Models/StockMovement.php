<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable change in stock (plan.md §17).
 *
 * Quantity is signed: a sale is negative, a restock is positive. Movements
 * are never edited or deleted, so the running balance can always be rebuilt
 * and audited.
 */
#[Fillable([
    'organization_id',
    'product_id',
    'sale_id',
    'user_id',
    'quantity',
    'reason',
    'notes',
    'created_at',
])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;

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
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}

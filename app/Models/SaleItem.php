<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in a sale.
 *
 * Name, SKU and price are snapshots taken at the moment of sale, so renaming a
 * product or changing its price can never rewrite what someone was charged.
 */
#[Fillable([
    'sale_id',
    'product_id',
    'product_name',
    'sku',
    'unit_price',
    'quantity',
    'line_total',
    'tax_rate',
])]
class SaleItem extends Model
{
    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->line_total);
    }
}

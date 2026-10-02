<?php

namespace App\Models;

use App\Enums\StockTransferStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Stock moving between branches (plan.md §18).
 *
 * A transfer is never a silent edit of a balance: sending creates a movement
 * out, and receiving creates a movement in. If stock goes missing in transit,
 * the ledger shows exactly where.
 *
 * @property StockTransferStatus $status
 */
#[Fillable([
    'organization_id',
    'product_id',
    'from_branch_id',
    'to_branch_id',
    'user_id',
    'reference',
    'quantity',
    'status',
    'notes',
    'received_at',
])]
class StockTransfer extends Model
{
    use BelongsToOrganization;

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
            'status' => StockTransferStatus::class,
            'quantity' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transfer): void {
            $transfer->reference ??= 'TRF-'.strtoupper(Str::random(8));
        });
    }
}

<?php

namespace App\Enums;

/**
 * Stock transfer lifecycle (plan.md §18).
 *
 * Deliberately separate from PurchaseOrderStatus: a transfer has no drafts and
 * no partial receipts, so sharing an enum would invite impossible states.
 */
enum StockTransferStatus: string
{
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function isSettled(): bool
    {
        return $this !== self::IN_TRANSIT;
    }

    public function label(): string
    {
        return match ($this) {
            self::IN_TRANSIT => 'In transit',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        };
    }
}

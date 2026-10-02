<?php

namespace App\Enums;

/**
 * Purchase order lifecycle (plan.md §18).
 */
enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case ORDERED = 'ordered';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    /**
     * Whether more stock can still be booked in against this order.
     */
    public function acceptsReceiving(): bool
    {
        return in_array($this, [self::ORDERED, self::PARTIAL], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::RECEIVED, self::CANCELLED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ORDERED => 'Ordered',
            self::PARTIAL => 'Partially received',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        };
    }
}

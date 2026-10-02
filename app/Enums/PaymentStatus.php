<?php

namespace App\Enums;

/**
 * Payment lifecycle (plan.md §13).
 *
 * Only PAID releases the booking from its outstanding balance; REFUNDED and
 * PARTIALLY_REFUNDED mean money came back.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public function isSettled(): bool
    {
        return in_array($this, [
            self::PAID,
            self::PARTIALLY_REFUNDED,
            self::REFUNDED,
        ], true);
    }

    public function isFailed(): bool
    {
        return in_array($this, [self::FAILED, self::EXPIRED], true);
    }

    public function isRefundable(): bool
    {
        return in_array($this, [self::PAID, self::PARTIALLY_REFUNDED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::PARTIALLY_REFUNDED => 'Partially refunded',
            self::REFUNDED => 'Refunded',
            self::EXPIRED => 'Expired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PAID => 'bg-pickle-50 text-pickle-700',
            self::PENDING, self::PROCESSING => 'bg-energetic-50 text-energetic-700',
            self::FAILED, self::EXPIRED => 'bg-slate-100 text-slate-600',
            self::PARTIALLY_REFUNDED => 'bg-slate-100 text-slate-700',
            self::REFUNDED => 'bg-slate-200 text-slate-700',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

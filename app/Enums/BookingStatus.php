<?php

namespace App\Enums;

/**
 * Booking lifecycle (plan.md §12).
 *
 * Only PENDING, CONFIRMED and CHECKED_IN occupy a court. The rest are terminal
 * and release the slot, which is what keeps availability honest.
 */
enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';
    case REFUNDED = 'refunded';

    /**
     * Statuses that still hold the court for their time range.
     *
     * @return array<int, self>
     */
    public static function occupying(): array
    {
        return [self::PENDING, self::CONFIRMED, self::CHECKED_IN];
    }

    public function occupiesSlot(): bool
    {
        return in_array($this, self::occupying(), true);
    }

    /**
     * Whether the booking may still be changed or cancelled.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function isTerminal(): bool
    {
        return ! $this->occupiesSlot();
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::CHECKED_IN => 'Checked in',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No show',
            self::REFUNDED => 'Refunded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-energetic-50 text-energetic-700',
            self::CONFIRMED => 'bg-pickle-50 text-pickle-700',
            self::CHECKED_IN => 'bg-pickle-100 text-pickle-800',
            self::COMPLETED => 'bg-slate-100 text-slate-700',
            self::CANCELLED, self::NO_SHOW => 'bg-slate-100 text-slate-600',
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

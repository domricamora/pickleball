<?php

namespace App\Enums;

/**
 * POS sale lifecycle (plan.md §17).
 */
enum SaleStatus: string
{
    case OPEN = 'open';        // cart in progress, not yet paid
    case PAID = 'paid';
    case REFUNDED = 'refunded';
    case VOID = 'void';        // cancelled before payment

    public function isSettled(): bool
    {
        return $this === self::PAID;
    }

    public function isOpen(): bool
    {
        return $this === self::OPEN;
    }

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
            self::VOID => 'Voided',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PAID => 'bg-pickle-50 text-pickle-700',
            self::OPEN => 'bg-energetic-50 text-energetic-700',
            self::REFUNDED, self::VOID => 'bg-slate-100 text-slate-600',
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

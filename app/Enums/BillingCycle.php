<?php

namespace App\Enums;

/**
 * How often a membership bills (plan.md §15).
 */
enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';
    case CUSTOM = 'custom';

    /**
     * The number of months one cycle covers.
     */
    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::ANNUAL => 12,
            self::CUSTOM => 1, // custom_m_days decides, not the cycle
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::ANNUAL => 'Annual',
            self::CUSTOM => 'Custom',
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

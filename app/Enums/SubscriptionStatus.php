<?php

namespace App\Enums;

/**
 * What a membership is currently doing (plan.md §15).
 */
enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    /**
     * Whether credits may still be used.
     */
    public function canUseCredits(): bool
    {
        return $this === self::ACTIVE;
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-pickle-50 text-pickle-700',
            self::PAUSED => 'bg-energetic-50 text-energetic-700',
            self::EXPIRED, self::CANCELLED => 'bg-slate-100 text-slate-600',
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

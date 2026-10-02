<?php

namespace App\Enums;

/**
 * Court operational status (plan.md §11).
 */
enum CourtStatus: string
{
    case AVAILABLE = 'available';
    case MAINTENANCE = 'maintenance';
    case BLOCKED = 'blocked';
    case RETIRED = 'retired';

    /** Only an available court can accept a booking. */
    public function isBookable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::MAINTENANCE => 'Maintenance',
            self::BLOCKED => 'Blocked',
            self::RETIRED => 'Retired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-pickle-50 text-pickle-700',
            self::MAINTENANCE => 'bg-energetic-50 text-energetic-700',
            self::BLOCKED, self::RETIRED => 'bg-slate-100 text-slate-700',
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

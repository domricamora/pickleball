<?php

namespace App\Enums;

/**
 * Why a court is unavailable for a window (plan.md §11).
 */
enum BlockType: string
{
    case MAINTENANCE = 'maintenance';
    case HOLIDAY = 'holiday';
    case EVENT = 'event';
    case PRIVATE = 'private';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MAINTENANCE => 'Maintenance',
            self::HOLIDAY => 'Holiday',
            self::EVENT => 'Event reservation',
            self::PRIVATE => 'Private block',
            self::OTHER => 'Other',
        };
    }

    /**
     * Whether this block covers the whole day rather than a time window.
     */
    public function isAllDay(): bool
    {
        return $this === self::HOLIDAY;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

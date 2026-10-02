<?php

namespace App\Enums;

/**
 * Event lifecycle (plan.md §16).
 */
enum EventStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';       // accepting registrations
    case FULL = 'full';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function acceptsRegistrations(): bool
    {
        return in_array($this, [self::OPEN], true);
    }

    public function isOver(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::OPEN => 'Open',
            self::FULL => 'Full',
            self::ONGOING => 'Ongoing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
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

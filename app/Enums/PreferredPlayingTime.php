<?php

namespace App\Enums;

/**
 * When a player prefers to play (plan.md §14).
 */
enum PreferredPlayingTime: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
    case ANY = 'any';

    /**
     * The local hours this preference implies, or null for "any".
     *
     * @return array{0: int, 1: int}|null
     */
    public function window(): ?array
    {
        return match ($this) {
            self::MORNING => [6, 12],
            self::AFTERNOON => [12, 17],
            self::EVENING => [17, 23],
            self::ANY => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MORNING => 'Mornings',
            self::AFTERNOON => 'Afternoons',
            self::EVENING => 'Evenings',
            self::ANY => 'Any time',
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

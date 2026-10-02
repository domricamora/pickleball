<?php

namespace App\Enums;

/**
 * How good a player is (plan.md §14).
 *
 * Ordered from least to most experienced so comparisons are meaningful.
 */
enum SkillLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';
    case COMPETITIVE = 'competitive';

    /**
     * A rough rank, used for sorting and for seeding event divisions.
     */
    public function rank(): int
    {
        return match ($this) {
            self::BEGINNER => 1,
            self::INTERMEDIATE => 2,
            self::ADVANCED => 3,
            self::COMPETITIVE => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
            self::COMPETITIVE => 'Competitive',
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

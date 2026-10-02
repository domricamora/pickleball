<?php

namespace App\Enums;

/**
 * Playing surface (plan.md §11).
 */
enum CourtSurface: string
{
    case HARD = 'hard';
    case SOFT = 'soft';
    case ACRYLIC = 'acrylic';
    case CUSHIONED = 'cushioned';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HARD => 'Hard court',
            self::SOFT => 'Soft court',
            self::ACRYLIC => 'Acrylic',
            self::CUSHIONED => 'Cushioned',
            self::OTHER => 'Other',
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

<?php

namespace App\Enums;

/**
 * Pricing categories a facility can publish (plan.md §11, §32).
 *
 * Order matters: `PriceType::resolveFor()` applies the most specific rule
 * first, so a holiday or peak price beats a plain weekday rate.
 */
enum PriceType: string
{
    case WEEKDAY = 'weekday';
    case WEEKEND = 'weekend';
    case PEAK = 'peak';
    case OFF_PEAK = 'off_peak';
    case HOLIDAY = 'holiday';
    case MEMBER = 'member';
    case GUEST = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::WEEKDAY => 'Weekday',
            self::WEEKEND => 'Weekend',
            self::PEAK => 'Peak hours',
            self::OFF_PEAK => 'Off-peak',
            self::HOLIDAY => 'Holiday',
            self::MEMBER => 'Member rate',
            self::GUEST => 'Guest rate',
        };
    }

    /**
     * How specific this rule is. A higher number wins.
     */
    public function priority(): int
    {
        return match ($this) {
            self::HOLIDAY => 60,
            self::PEAK => 50,
            self::MEMBER, self::GUEST => 40,
            self::WEEKEND => 30,
            self::WEEKDAY => 20,
            self::OFF_PEAK => 10,
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

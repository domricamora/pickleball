<?php

namespace App\Enums;

/**
 * What a product is (plan.md §17).
 */
enum ProductCategory: string
{
    case PADDLE = 'paddle';
    case BALL = 'ball';
    case GRIP = 'grip';
    case BAG = 'bag';
    case APPAREL = 'apparel';
    case DRINK = 'drink';
    case SNACK = 'snack';
    case ACCESSORY = 'accessory';
    case RENTAL = 'rental';

    /**
     * Equipment hired per session rather than sold outright.
     */
    public function isRental(): bool
    {
        return $this === self::RENTAL;
    }

    public function label(): string
    {
        return match ($this) {
            self::PADDLE => 'Paddles',
            self::BALL => 'Balls',
            self::GRIP => 'Grips',
            self::BAG => 'Bags',
            self::APPAREL => 'Apparel',
            self::DRINK => 'Drinks',
            self::SNACK => 'Snacks',
            self::ACCESSORY => 'Accessories',
            self::RENTAL => 'Rental equipment',
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

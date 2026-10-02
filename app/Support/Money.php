<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * The single peso formatter (plan.md §13).
 *
 * Every amount in the product goes through here so the whole app renders
 * Philippine pesos identically: ₱1,250.00.
 */
class Money
{
    /**
     * Format pesos for display, e.g. ₱1,250.00.
     */
    public static function format(int|float|string|null $amount, bool $withSymbol = true): string
    {
        $value = self::toFloat($amount);
        $formatted = number_format($value, 2, '.', ',');

        return $withSymbol
            ? config('platform.locale.currency_symbol').$formatted
            : $formatted;
    }

    /**
     * Format for display without the symbol, e.g. 1,250.00.
     */
    public static function number(int|float|string|null $amount): string
    {
        return number_format(self::toFloat($amount), 2, '.', ',');
    }

    /**
     * Cast user input to a non-negative peso amount.
     *
     * Rejects anything that is not a plain decimal so a hostile string
     * cannot reach the database as a number.
     *
     * @throws InvalidArgumentException
     */
    public static function toFloat(int|float|string|null $amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        if (is_int($amount) || is_float($amount)) {
            $value = (float) $amount;
        } else {
            $trimmed = trim($amount);

            if (! preg_match('/^-?\d+(\.\d+)?$/', $trimmed)) {
                throw new InvalidArgumentException("'{$amount}' is not a valid peso amount.");
            }

            $value = (float) $trimmed;
        }

        // Pesos have two decimal places; anything finer is a data error.
        return round($value, 2);
    }

    /**
     * Format a peso amount with no decimals, for prose such as "₱1,250".
     */
    public static function whole(int|float|string|null $amount, bool $withSymbol = true): string
    {
        $value = number_format(self::toFloat($amount), 0, '.', ',');

        return $withSymbol ? config('platform.locale.currency_symbol').$value : $value;
    }
}

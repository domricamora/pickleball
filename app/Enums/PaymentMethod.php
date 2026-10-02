<?php

namespace App\Enums;

/**
 * How a booking or order is paid for (plan.md §13).
 *
 * Gateway-backed methods (gcash, maya, card) are captured remotely; the rest
 * are settled by staff and recorded manually.
 */
enum PaymentMethod: string
{
    case GCASH = 'gcash';
    case MAYA = 'maya';
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case POS = 'pos';

    /**
     * Whether the gateway settles the payment for us.
     */
    public function isGatewayBacked(): bool
    {
        return in_array($this, [self::GCASH, self::MAYA, self::CARD], true);
    }

    /**
     * Whether staff must confirm settlement at the counter.
     */
    public function requiresManualSettlement(): bool
    {
        return ! $this->isGatewayBacked();
    }

    public function label(): string
    {
        return match ($this) {
            self::GCASH => 'GCash',
            self::MAYA => 'Maya',
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank transfer',
            self::CASH => 'Cash',
            self::POS => 'POS / card terminal',
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

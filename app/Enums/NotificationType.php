<?php

namespace App\Enums;

/**
 * The notifications the product sends (plan.md §21).
 */
enum NotificationType: string
{
    case BOOKING_CONFIRMATION = 'booking_confirmation';
    case BOOKING_REMINDER = 'booking_reminder';
    case BOOKING_CANCELLED = 'booking_cancelled';
    case BOOKING_RESCHEDULED = 'booking_rescheduled';
    case PAYMENT_CONFIRMATION = 'payment_confirmation';
    case PAYMENT_FAILED = 'payment_failed';
    case EVENT_REGISTRATION = 'event_registration';
    case MEMBERSHIP_EXPIRING = 'membership_expiring';
    case PACKAGE_EXPIRING = 'package_expiring';
    case MARKETING = 'marketing';

    /**
     * Transactional messages a customer must receive. Marketing is opt-in.
     */
    public function isTransactional(): bool
    {
        return $this !== self::MARKETING;
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

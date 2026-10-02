<?php

namespace App\Enums;

/**
 * Where a notification is delivered (plan.md §21).
 *
 * SMS is modelled but not wired to a provider in this phase: the channel
 * exists so consent can be enforced and the integration can be added without
 * a migration.
 */
enum NotificationChannel: string
{
    case IN_APP = 'in_app';
    case EMAIL = 'email';
    case SMS = 'sms';

    /**
     * Whether a channel needs the customer to have opted in.
     *
     * Transactional messages bypass consent, because they are about a booking
     * the customer made; marketing never does.
     */
    public function requiresConsent(NotificationType $type): bool
    {
        return ! $type->isTransactional();
    }

    public function label(): string
    {
        return match ($this) {
            self::IN_APP => 'In app',
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
        };
    }
}

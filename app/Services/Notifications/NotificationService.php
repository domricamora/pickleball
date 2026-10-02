<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Jobs\DeliverNotification;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Composing and queueing notifications (plan.md §21).
 *
 * Nothing is sent synchronously: a row is written and a job is dispatched, so
 * a slow or broken provider can never delay a customer's booking. The row is
 * the audit trail of what was intended; the job records the outcome.
 */
class NotificationService
{
    /**
     * Queue a notification for a customer.
     *
     * @param  array<string, mixed>  $context  booking_id, event_id, etc.
     *
     * @throws RuntimeException when consent is required but not given
     */
    public function notify(
        Customer $customer,
        NotificationType $type,
        string $body,
        ?string $subject = null,
        NotificationChannel $channel = NotificationChannel::IN_APP,
        array $context = [],
    ): Notification {
        $this->guardConsent($customer, $type, $channel);

        $notification = Notification::create([
            'organization_id' => $customer->organization_id,
            'customer_id' => $customer->id,
            'type' => $type,
            'channel' => $channel,
            'subject' => $subject ?? $type->label(),
            'body' => $body,
            'status' => 'queued',
        ] + $context);

        DeliverNotification::dispatch($notification);

        return $notification;
    }

    /**
     * Mark a notification as read. Idempotent, so a double tap is harmless.
     */
    public function markRead(Notification $notification): Notification
    {
        if ($notification->isRead()) {
            return $notification;
        }

        $notification->update([
            'read_at' => now(),
            'status' => 'read',
        ]);

        return $notification;
    }

    /**
     * Unread notifications for a customer, newest first.
     *
     * @return Collection<int, Notification>
     */
    public function unreadFor(Customer $customer, int $limit = 20): Collection
    {
        return $customer->notifications()
            ->unread()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Refuse marketing to someone who has not opted in.
     *
     * Transactional messages are about a booking the customer made, so they
     * are always allowed. An in-app message needs no opt-in either.
     */
    protected function guardConsent(
        Customer $customer,
        NotificationType $type,
        NotificationChannel $channel,
    ): void {
        if (! $channel->requiresConsent($type)) {
            return;
        }

        $consented = match ($channel) {
            NotificationChannel::EMAIL => $customer->consents_to_marketing,
            NotificationChannel::SMS => $customer->consents_to_sms,
            NotificationChannel::IN_APP => true,
        };

        if (! $consented) {
            throw new RuntimeException(
                "{$customer->fullName()} has not consented to {$channel->label()} marketing."
            );
        }
    }
}

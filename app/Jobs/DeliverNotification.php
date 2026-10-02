<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Delivers one queued notification (plan.md §21).
 *
 * Delivery never throws: a failure is recorded on the notification so the
 * reason survives, rather than only living in the queue's dead-letter table.
 */
class DeliverNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Notification $notification) {}

    public function handle(): void
    {
        $notification = $this->notification;

        if ($notification->status === 'sent') {
            return; // Already delivered; a retry must not send twice.
        }

        try {
            $this->deliver($notification);

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failure_reason' => null,
            ]);
        } catch (Throwable $exception) {
            $notification->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            // Re-thrown so the queue records the failure and can retry.
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Notification delivery failed', [
            'notification_id' => $this->notification->id,
            'reason' => $exception->getMessage(),
        ]);
    }

    /**
     * The actual send.
     *
     * In-app delivery is the row itself, so it needs no provider. Email and
     * SMS are wired to Laravel's mailer and log respectively; a real SMS
     * gateway is Phase 21 follow-up and is deliberately not faked here.
     */
    protected function deliver(Notification $notification): void
    {
        $customer = $notification->customer;

        if ($customer === null) {
            throw new \RuntimeException('Notification has no customer to deliver to.');
        }

        match ($notification->channel) {
            NotificationChannel::IN_APP => null,
            NotificationChannel::EMAIL => $this->sendEmail($customer, $notification),
            NotificationChannel::SMS => $this->sendSms($customer, $notification),
        };
    }

    protected function sendEmail(Customer $customer, Notification $notification): void
    {
        if ($customer->email === null) {
            throw new \RuntimeException('Customer has no email address.');
        }

        Mail::raw($notification->body, function ($message) use ($customer, $notification): void {
            $message->to($customer->email)
                ->subject($notification->subject ?? $notification->type->label());
        });
    }

    protected function sendSms(Customer $customer, Notification $notification): void
    {
        if ($customer->mobile === null) {
            throw new \RuntimeException('Customer has no mobile number.');
        }

        // No provider is configured in this phase, so SMS is recorded rather
        // than silently pretending to have been delivered.
        Log::info('SMS queued (no provider configured)', [
            'mobile' => $customer->mobile,
            'body' => $notification->body,
        ]);
    }
}

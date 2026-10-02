<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Jobs\DeliverNotification;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Organization;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 13 acceptance checks: notifications, consent and queueing
 * (plan.md §21).
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected NotificationService $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();

        $this->customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'player@example.com',
            'mobile' => '09171234567',
            'consents_to_marketing' => false,
            'consents_to_sms' => false,
        ]);

        $this->notifications = app(NotificationService::class);
    }

    public function test_a_notification_is_queued_not_sent_synchronously(): void
    {
        Bus::fake();

        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Your court is booked.',
        );

        $this->assertSame('queued', $notification->status);
        $this->assertSame(NotificationChannel::IN_APP, $notification->channel);

        // Delivery is a job, so a slow provider cannot delay a booking.
        Bus::assertDispatched(DeliverNotification::class);
    }

    public function test_delivery_runs_the_job_and_marks_it_sent(): void
    {
        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Booked.',
        );

        (new DeliverNotification($notification))->handle();

        $this->assertSame('sent', $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->sent_at);
    }

    public function test_a_retry_does_not_send_twice(): void
    {
        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Booked.',
        );

        $job = new DeliverNotification($notification);
        $job->handle();
        $firstSentAt = $notification->fresh()->sent_at;

        // The queue retries on any failure; a second run must be a no-op.
        (new DeliverNotification($notification->fresh()))->handle();

        $this->assertTrue($firstSentAt->eq($notification->fresh()->sent_at));
    }

    public function test_a_failed_delivery_records_the_reason(): void
    {
        // The queue is faked so the job runs inline, and only the mailer is
        // mocked; a full facade mock would also intercept the dispatch.
        Bus::fake();
        Mail::shouldReceive('raw')->once()->andThrow(new \Exception('SMTP down'));

        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::PAYMENT_CONFIRMATION,
            'Payment received.',
            'Receipt',
            NotificationChannel::EMAIL,
        );

        try {
            (new DeliverNotification($notification))->handle();
        } catch (\Exception) {
            // Expected; the job re-throws so the queue can retry.
        }

        $fresh = $notification->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertSame('SMTP down', $fresh->failure_reason);
    }

    public function test_marking_read_is_idempotent(): void
    {
        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Booked.',
        );

        $this->notifications->markRead($notification);
        $first = $notification->fresh()->read_at;

        $this->notifications->markRead($notification->fresh());

        $this->assertTrue($first->eq($notification->fresh()->read_at), 'A double tap must not move read_at.');
    }

    public function test_unread_notifications_are_listed_newest_first(): void
    {
        $first = $this->notifications->notify($this->customer, NotificationType::BOOKING_CONFIRMATION, 'One');
        $this->notifications->markRead($first);
        $this->notifications->notify($this->customer, NotificationType::PAYMENT_CONFIRMATION, 'Two');

        $unread = $this->notifications->unreadFor($this->customer);

        $this->assertCount(1, $unread);
        $this->assertSame(NotificationType::PAYMENT_CONFIRMATION, $unread->first()->type);
    }

    public function test_marketing_email_needs_consent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has not consented');

        $this->notifications->notify(
            $this->customer,
            NotificationType::MARKETING,
            'Come play!',
            'Special offer',
            NotificationChannel::EMAIL,
        );
    }

    public function test_markaging_sms_needs_separate_consent(): void
    {
        $this->customer->update(['consents_to_marketing' => true, 'consents_to_sms' => false]);

        $this->expectException(RuntimeException::class);

        $this->notifications->notify(
            $this->customer,
            NotificationType::MARKETING,
            'Come play!',
            null,
            NotificationChannel::SMS,
        );
    }

    public function test_markaging_goes_out_once_consented(): void
    {
        $this->customer->update(['consents_to_marketing' => true]);

        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::MARKETING,
            'Come play!',
            'Special offer',
            NotificationChannel::EMAIL,
        );

        $this->assertSame('queued', $notification->status);
    }

    public function test_transactional_email_needs_no_consent(): void
    {
        // A booking confirmation is about something the player already did.
        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Your court is booked.',
            'Booking confirmed',
            NotificationChannel::EMAIL,
        );

        $this->assertSame('queued', $notification->status);
    }

    public function test_notifications_are_tenant_scoped(): void
    {
        $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Booked.',
        );

        $otherCustomer = Customer::factory()->create();

        $this->assertSame(1, $this->customer->notifications()->count());
        $this->assertSame(0, $otherCustomer->notifications()->count());
    }

    public function test_a_notification_keeps_its_reference_to_the_booking(): void
    {
        $org = Organization::whereKey($this->customer->organization_id)->firstOrFail();
        $branch = Branch::factory()->for($org)->create();
        $court = Court::factory()->for($org)->for($branch)->create();

        $booking = Booking::factory()->create([
            'organization_id' => $this->customer->organization_id,
            'branch_id' => $branch->id,
            'court_id' => $court->id,
            'customer_id' => $this->customer->id,
        ]);

        $notification = $this->notifications->notify(
            $this->customer,
            NotificationType::BOOKING_CONFIRMATION,
            'Booked.',
            null,
            NotificationChannel::IN_APP,
            ['booking_id' => $booking->id],
        );

        $this->assertSame($booking->id, $notification->booking_id);
    }
}

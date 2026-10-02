<?php

namespace Tests\Feature\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Gateway callback security and idempotency (plan.md §13).
 *
 * A webhook is the one endpoint an attacker can reach without an account, so
 * it must prove the payload really came from the gateway before it is trusted.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected const SECRET = 'whsec_test_abc123';

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paymongo.webhook_secret' => self::SECRET,
            'services.paymongo.secret_key' => 'sk_test_key',
        ]);

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();
        $court = Court::factory()->for($organization)->for($branch)->create();

        $this->booking = Booking::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'court_id' => $court->id,
            'status' => BookingStatus::PENDING,
            'amount' => '400.00',
        ]);
    }

    /**
     * Build a signed request the way PayMongo would.
     */
    protected function signedPost(array $payload, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload);

        return $this->postGateway($body, $signature ?? self::sign($body));
    }

    /**
     * Sign a raw body exactly as PayMongo would.
     */
    protected static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, self::SECRET);
    }

    /**
     * Post a raw JSON body with an explicit signature header.
     */
    protected function postGateway(string $body, ?string $signature): TestResponse
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        if ($signature !== null) {
            // Headers must travel in the server array; withHeaders() is not
            // carried into call() with an explicit $server argument.
            $server['HTTP_X_PAYMONGO_SIGNATURE'] = $signature;
        }

        return $this->call('POST', '/payments/webhook', [], [], [], $server, $body);
    }

    protected function payload(string $type, string $reference): array
    {
        return [
            'data' => [
                'attributes' => [
                    'type' => $type,
                    'data' => [
                        'attributes' => [
                            'payment_reference' => $reference,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function pendingPayment(): Payment
    {
        return app(PaymentService::class)->record($this->booking, PaymentMethod::GCASH, 400.00);
    }

    public function test_an_unsigned_webhook_is_rejected(): void
    {
        $payment = $this->pendingPayment();

        $response = $this->postJson('/payments/webhook', $this->payload('payment.paid', $payment->reference));

        $response->assertUnauthorized();

        // The forged request must not have moved any money.
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_a_webhook_with_a_wrong_signature_is_rejected(): void
    {
        $payment = $this->pendingPayment();

        $response = $this->postGateway(
            json_encode($this->payload('payment.paid', $payment->reference)),
            'not-the-right-signature',
        );

        $response->assertUnauthorized();
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_a_tampered_payload_is_rejected(): void
    {
        $payment = $this->pendingPayment();

        // Sign one body, then send a different one.
        $response = $this->postGateway(
            json_encode($this->payload('payment.paid', $payment->reference)),
            self::sign('{"type":"payment.failed"}'),
        );

        $response->assertUnauthorized();
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_a_signed_paid_webhook_settles_the_payment(): void
    {
        $payment = $this->pendingPayment();

        $this->signedPost($this->payload('payment.paid', $payment->reference))
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(BookingStatus::CONFIRMED, $this->booking->fresh()->status);
    }

    public function test_a_signed_failed_webhook_marks_the_payment_failed(): void
    {
        $payment = $this->pendingPayment();

        $this->signedPost($this->payload('payment.failed', $payment->reference))->assertOk();

        $fresh = $payment->fresh();
        $this->assertSame(PaymentStatus::FAILED, $fresh->status);
        $this->assertNotNull($fresh->failure_reason);
    }

    public function test_a_retried_webhook_does_not_double_count(): void
    {
        $payment = $this->pendingPayment();
        $payload = $this->payload('payment.paid', $payment->reference);

        $this->signedPost($payload)->assertOk();
        $firstPaidAt = $payment->fresh()->paid_at;

        // Gateways retry on any non-2xx or timeout, so this must be safe.
        $this->signedPost($payload)->assertOk();
        $this->signedPost($payload)->assertOk();

        $fresh = $payment->fresh();
        $this->assertSame(PaymentStatus::PAID, $fresh->status);
        $this->assertTrue($firstPaidAt->eq($fresh->paid_at), 'paid_at must not move on a retry.');
    }

    public function test_a_webhook_for_an_unknown_payment_is_acknowledged(): void
    {
        $response = $this->signedPost($this->payload('payment.paid', 'PAY-DOESNOTEXIST'));

        // 200 so the gateway stops retrying something we will never accept.
        $response->assertOk();
    }

    public function test_a_webhook_without_a_configured_secret_is_rejected(): void
    {
        config(['services.paymongo.webhook_secret' => null]);
        $payment = $this->pendingPayment();

        $this->signedPost($this->payload('payment.paid', $payment->reference))->assertUnauthorized();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }
}

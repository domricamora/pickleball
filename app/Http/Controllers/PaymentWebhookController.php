<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\Gateways\PaymentGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gateway callbacks (plan.md §13).
 *
 * Two rules govern this endpoint:
 *  1. the signature is checked before the body is read, so an unsigned or
 *     forged request can never move money;
 *  2. handling is idempotent, because gateways retry.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $payments,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // The raw body is what was signed, so read it untouched.
        $payload = $request->getContent();
        $signature = $request->header('X-PayMongo-Signature');

        if (! $this->gateway->verifySignature($payload, $signature)) {
            // 401: we cannot prove this came from the gateway.
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return response()->json(['message' => 'Malformed payload.'], 422);
        }

        $eventType = (string) ($data['data']['attributes']['type'] ?? $data['type'] ?? '');

        $reference = $data['data']['attributes']['data']['attributes']['payment_reference']
            ?? $data['data']['attributes']['metadata']['payment_reference']
            ?? null;

        if (! is_string($reference) || $reference === '') {
            return response()->json(['message' => 'Missing payment reference.'], 422);
        }

        $payment = Payment::withoutGlobalScope('organization')
            ->where(function ($query) use ($reference): void {
                // Grouped: an ungrouped orWhere() would drop the first condition.
                $query->where('reference', $reference)
                    ->orWhere('gateway_payment_id', $reference);
            })
            ->first();

        if ($payment === null) {
            // Nothing to do; 200 so the gateway stops retrying.
            return response()->json(['message' => 'Unknown payment.'], 200);
        }

        match (true) {
            $this->isSuccessful($eventType) => $this->settle($payment),
            $this->isFailed($eventType) => $this->fail($payment, $eventType),
            default => null,
        };

        return response()->json(['received' => true], 200);
    }

    /**
     * Settle the payment. markPaid is idempotent, so retries are safe.
     */
    protected function settle(Payment $payment): void
    {
        $this->payments->markPaid($payment, (string) $payment->gateway_payment_id);
    }

    protected function fail(Payment $payment, string $eventType): void
    {
        $reason = match ($eventType) {
            'payment.failed' => 'The payment was declined by the gateway.',
            'payment.expired' => 'The payment link expired before it was completed.',
            default => 'The payment did not complete.',
        };

        $this->payments->markFailed($payment, $reason);
    }

    protected function isSuccessful(string $eventType): bool
    {
        return in_array($eventType, [
            'payment.paid',
            'checkout_session.paid',
            'payment_succeeded',
        ], true);
    }

    protected function isFailed(string $eventType): bool
    {
        return in_array($eventType, [
            'payment.failed',
            'payment.expired',
            'payment_failed',
        ], true);
    }
}

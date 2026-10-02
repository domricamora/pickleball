<?php

namespace App\Services\Payments\Gateways\Drivers;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Services\Payments\Gateways\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayMongo: GCash, Maya and cards (plan.md §13).
 *
 * Credentials come from config, never from the database or a commit. Card
 * details are handed straight to PayMongo and are never persisted by us.
 */
class PayMongoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paymongo';
    }

    /**
     * @return array<int, PaymentMethod>
     */
    public function supportedMethods(): array
    {
        return [PaymentMethod::GCASH, PaymentMethod::MAYA, PaymentMethod::CARD];
    }

    public function supports(PaymentMethod $method): bool
    {
        return in_array($method, $this->supportedMethods(), true);
    }

    /**
     * @param  array{description?: string|null}  $metadata
     * @return array{redirect_url: string|null, reference: string, instructions?: string|null}
     */
    public function createIntent(Payment $payment, array $metadata = []): array
    {
        // PayMongo amounts are in the smallest currency unit: centavos.
        $amountInCentavos = (int) round(((float) $payment->amount) * 100);

        $response = $this->client()->post($this->baseUrl().'/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name' => $metadata['description'] ?? 'PicklePlay booking',
                    ],
                    'description' => $metadata['description'] ?? 'Court booking',
                    'line_items' => [
                        [
                            'amount' => $amountInCentavos,
                            'currency' => 'PHP',
                            'name' => $metadata['description'] ?? 'Court booking',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => [$this->checkoutTypeFor($payment->method)],
                    'success_url' => route('payments.return', ['reference' => $payment->reference]),
                    'cancel_url' => route('payments.cancelled', ['reference' => $payment->reference]),
                    'metadata' => [
                        'payment_reference' => $payment->reference,
                        'booking_id' => (string) $payment->booking_id,
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'PayMongo could not start the payment: '.$response->json('errors.0.detail', 'unknown error')
            );
        }

        $attributes = $response->json('data.attributes', []);

        return [
            'redirect_url' => $attributes['checkout_url'] ?? null,
            'reference' => (string) ($attributes['id'] ?? ''),
            'instructions' => null,
        ];
    }

    /**
     * PayMongo signs callbacks with a keyed HMAC-SHA256 over the raw body.
     */
    public function verifySignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.paymongo.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            // Without a secret we cannot honestly claim the callback is real.
            return false;
        }

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): ?string
    {
        // Refunds are handled in the facility's dashboard in this phase; the
        // record is still written so the ledger stays honest.
        return null;
    }

    /**
     * Map our method onto PayMongo's checkout types.
     */
    protected function checkoutTypeFor(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::GCASH => 'gcash',
            PaymentMethod::MAYA => 'paymaya',
            PaymentMethod::CARD => 'card',
            default => throw new RuntimeException("PayMongo cannot settle {$method->value}."),
        };
    }

    protected function client(): PendingRequest
    {
        return Http::withToken((string) config('services.paymongo.secret_key'))
            ->acceptJson()
            ->asJson();
    }

    protected function baseUrl(): string
    {
        $base = config('services.paymongo.base_url');

        if (! is_string($base) || $base === '') {
            throw new RuntimeException('PayMongo base URL is not configured.');
        }

        return rtrim($base, '/');
    }
}

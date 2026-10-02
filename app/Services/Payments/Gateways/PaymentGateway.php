<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Payment;

/**
 * A payment gateway (plan.md §13).
 *
 * The abstraction exists so PayMongo can be swapped for another Philippine
 * gateway — or a fake in tests — without touching the booking flow.
 */
interface PaymentGateway
{
    /**
     * The short name recorded on the payment row.
     */
    public function name(): string;

    /**
     * Methods this gateway can actually settle.
     *
     * @return array<int, PaymentMethod>
     */
    public function supportedMethods(): array;

    public function supports(PaymentMethod $method): bool;

    /**
     * Start a payment and return whatever the customer must be redirected to.
     *
     * @param  array{description?: string|null}  $metadata
     * @return array{redirect_url: string|null, reference: string, instructions?: string|null}
     */
    public function createIntent(Payment $payment, array $metadata = []): array;

    /**
     * Whether a callback genuinely came from the gateway.
     */
    public function verifySignature(string $payload, ?string $signature): bool;

    /**
     * Issue a refund. Drivers that cannot refund remotely report null and the
     * facility settles it by hand.
     */
    public function refund(Payment $payment, float $amount, ?string $reason = null): ?string;
}

<?php

namespace App\Services\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Payments\Gateways\PaymentGateway;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * The payment ledger (plan.md §13).
 *
 * Every state change locks the payment row first, so two staff members (or a
 * webhook and a cashier) acting at the same instant cannot both move the same
 * money twice.
 */
class PaymentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Record a payment against a booking.
     *
     * @param  array{user_id?: int|null, recorded_by?: int|null, card_last_four?: string|null, notes?: string|null}  $attributes
     */
    public function record(Booking $booking, PaymentMethod $method, float $amount, array $attributes = []): Payment
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('A payment must be greater than zero.');
        }

        if ($attributes['card_last_four'] ?? null) {
            // Defensive: refuse anything that is not four digits.
            $attributes['card_last_four'] = $this->sanitizeLastFour($attributes['card_last_four']);
        }

        return Payment::create([
            'organization_id' => $booking->organization_id,
            'branch_id' => $booking->branch_id,
            'booking_id' => $booking->id,
            'user_id' => $attributes['user_id'] ?? null,
            'recorded_by' => $attributes['recorded_by'] ?? null,
            'method' => $method,
            'status' => $method->isGatewayBacked() ? PaymentStatus::PENDING : PaymentStatus::PAID,
            'amount' => Money::toFloat($amount),
            'currency' => 'PHP',
            'gateway' => $method->isGatewayBacked() ? $this->gateway->name() : null,
            'card_last_four' => $attributes['card_last_four'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'paid_at' => $method->isGatewayBacked() ? null : now(),
        ]);
    }

    /**
     * Start a gateway checkout and return where to send the customer.
     *
     * @return array{redirect_url: string|null, reference: string}
     */
    public function startCheckout(Payment $payment, array $metadata = []): array
    {
        if (! $payment->method->isGatewayBacked()) {
            throw new RuntimeException("{$payment->method->label()} is settled at the counter, not by the gateway.");
        }

        $intent = $this->gateway->createIntent($payment, $metadata);

        $payment->forceFill([
            'status' => PaymentStatus::PROCESSING,
            'gateway_payment_id' => $intent['reference'] ?: null,
        ])->save();

        return $intent;
    }

    /**
     * Mark a payment settled. Safe to call twice — a webhook often retries.
     */
    public function markPaid(Payment $payment, ?string $gatewayReference = null): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayReference): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PaymentStatus::PAID) {
                return $locked; // Idempotent.
            }

            if ($locked->status->isFailed()) {
                throw new RuntimeException("A {$locked->status->label()} payment cannot be settled.");
            }

            $locked->status = PaymentStatus::PAID;
            $locked->paid_at = now();
            $locked->gateway_reference = $gatewayReference ?? $locked->gateway_reference;
            $locked->save();

            $this->confirmBooking($locked);

            return $locked;
        }, 3);
    }

    /**
     * Mark a payment failed, recording why.
     */
    public function markFailed(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // A payment that already succeeded is not undone by a late failure.
            if ($locked->status->isSettled()) {
                return $locked;
            }

            $locked->status = PaymentStatus::FAILED;
            $locked->failed_at = now();
            $locked->failure_reason = $reason;
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * Refund a payment, in full or in part.
     *
     * The whole operation is one locked transaction so two refunds can never
     * together exceed what was actually charged.
     */
    public function refund(Payment $payment, float $amount, ?User $actor = null, ?string $reason = null): PaymentRefund
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('A refund must be greater than zero.');
        }

        return DB::transaction(function () use ($payment, $amount, $actor, $reason): PaymentRefund {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isRefundable()) {
                throw new RuntimeException("A {$locked->status->label()} payment cannot be refunded.");
            }

            $refundable = $locked->refundableAmount();

            if ($amount > $refundable) {
                throw new RuntimeException(
                    'You cannot refund more than was collected. '
                    .Money::format($refundable).' is still refundable.'
                );
            }

            $refund = $locked->refunds()->create([
                'user_id' => $actor?->id,
                'processed_by' => $actor?->id,
                'amount' => Money::toFloat($amount),
                'currency' => 'PHP',
                'reason' => $reason,
            ]);

            $locked->refunded_amount = round((float) $locked->refunded_amount + $amount, 2);

            $locked->status = $locked->isFullyRefunded()
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED;

            $locked->refunded_at = now();
            $locked->save();

            if ($locked->status === PaymentStatus::REFUNDED) {
                $locked->booking?->update(['status' => BookingStatus::REFUNDED]);
            }

            return $refund;
        }, 3);
    }

    /**
     * Issue a receipt for a settled payment.
     */
    public function issueReceipt(Payment $payment, array $lineItems = []): Receipt
    {
        if (! $payment->status->isSettled()) {
            throw new RuntimeException('A receipt can only be issued for a settled payment.');
        }

        return Receipt::create([
            'organization_id' => $payment->organization_id,
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'subtotal' => $payment->amount,
            'total' => $payment->amount,
            'currency' => 'PHP',
            'line_items' => $lineItems,
            'issued_at' => now(),
        ]);
    }

    /**
     * A paid booking is confirmed; a still-pending one is promoted.
     */
    protected function confirmBooking(Payment $payment): void
    {
        $booking = $payment->booking;

        if ($booking === null) {
            return;
        }

        if ($booking->status === BookingStatus::PENDING) {
            $booking->update([
                'status' => BookingStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);
        }
    }

    /**
     * Only the last four digits are ever retained.
     */
    protected function sanitizeLastFour(string $digits): string
    {
        $clean = preg_replace('/\D/', '', $digits) ?? '';

        if (strlen($clean) !== 4) {
            throw new InvalidArgumentException('Only the last four digits of a card may be stored.');
        }

        return $clean;
    }
}

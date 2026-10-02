<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checkout and receipts (plan.md §13).
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /**
     * Choose how to pay for a booking.
     */
    public function create(Request $request, Booking $booking): View|RedirectResponse
    {
        return view('payments.create', [
            'booking' => $booking,
            'amount' => Money::format($booking->amount),
            'methods' => PaymentMethod::cases(),
            'reference' => $booking->reference,
        ]);
    }

    /**
     * Start a gateway checkout for a booking.
     */
    public function checkout(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', 'string', 'in:'.implode(',', PaymentMethod::values())],
        ]);

        $method = PaymentMethod::from($data['method']);
        $payment = $this->payments->record($booking, $method, (float) $booking->amount, [
            'user_id' => $request->user()?->id,
        ]);

        if (! $method->isGatewayBacked()) {
            // Cash, bank transfer and POS are settled at the counter.
            return to_route('payments.receipt', $payment)
                ->with('success', "{$method->label()} payment recorded.");
        }

        $intent = $this->payments->startCheckout($payment, [
            'description' => "Court booking {$booking->reference}",
        ]);

        if ($intent['redirect_url'] === null) {
            return to_route('payments.receipt', $payment)
                ->with('error', 'The gateway did not return a payment link. Please try again.');
        }

        return redirect()->away($intent['redirect_url']);
    }

    /**
     * Where the gateway sends the customer after a successful payment.
     */
    public function returned(Request $request, string $reference): RedirectResponse
    {
        $payment = Payment::withoutGlobalScope('organization')
            ->where('reference', $reference)
            ->first();

        if ($payment === null) {
            return to_route('book.index')->with('error', 'We could not find that payment.');
        }

        return to_route('payments.receipt', $payment);
    }

    public function cancelled(Request $request, string $reference): RedirectResponse
    {
        return to_route('book.index')->with('error', 'Payment was cancelled. Your slot was not booked.');
    }

    /**
     * The receipt for a payment.
     */
    public function receipt(Payment $payment): View
    {
        return view('payments.receipt', [
            'payment' => $payment,
            'amount' => Money::format($payment->amount),
        ]);
    }
}

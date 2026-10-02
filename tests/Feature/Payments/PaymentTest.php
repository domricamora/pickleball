<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 5 acceptance checks: payments, refunds and receipts (plan.md §13).
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();
        $court = Court::factory()->for($organization)->for($branch)->create();

        $this->booking = Booking::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'court_id' => $court->id,
            'amount' => '400.00',
        ]);
    }

    protected function service(): PaymentService
    {
        return app(PaymentService::class);
    }

    public function test_money_is_formatted_as_philippine_peso(): void
    {
        $this->assertSame('₱1,250.00', Money::format(1250));
        $this->assertSame('₱0.00', Money::format(0));
        $this->assertSame('₱1,250', Money::whole(1250));
        $this->assertSame('1,250.00', Money::number(1250));

        // Decimal strings from the database must format identically.
        $this->assertSame('₱400.00', Money::format('400.00'));
    }

    public function test_money_rejects_a_non_numeric_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toFloat('400; DROP TABLE payments');
    }

    public function test_a_gateway_payment_starts_pending(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::GCASH, 400.00);

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame('paymongo', $payment->gateway);
        $this->assertNull($payment->paid_at);
    }

    public function test_cash_is_settled_immediately(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);

        // Cash is collected at the counter, so it is already paid.
        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNull($payment->gateway);
    }

    public function test_a_zero_or_negative_payment_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service()->record($this->booking, PaymentMethod::CASH, 0);
    }

    public function test_paying_confirms_a_pending_booking(): void
    {
        $this->booking->update(['status' => 'pending']);

        $payment = $this->service()->record($this->booking, PaymentMethod::GCASH, 400.00);
        $this->service()->markPaid($payment);

        $this->assertSame('confirmed', $this->booking->fresh()->status->value);
    }

    public function test_marking_paid_twice_is_harmless(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::GCASH, 400.00);

        $first = $this->service()->markPaid($payment);
        $second = $this->service()->markPaid($payment);

        // Webhooks retry, so this must not error or double-count.
        $this->assertSame('paid', $second->status->value);
        $this->assertTrue($first->paid_at->eq($second->paid_at));
    }

    public function test_a_failed_payment_records_the_reason(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::MAYA, 400.00);

        $failed = $this->service()->markFailed($payment, 'Insufficient balance');

        $this->assertSame(PaymentStatus::FAILED, $failed->status);
        $this->assertSame('Insufficient balance', $failed->failure_reason);
    }

    public function test_a_late_failure_cannot_undo_a_successful_payment(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::MAYA, 400.00);
        $this->service()->markPaid($payment);

        $result = $this->service()->markFailed($payment, 'Late failure event');

        $this->assertSame(PaymentStatus::PAID, $result->status);
    }

    public function test_a_payment_can_be_refunded_in_full(): void
    {
        $staff = User::factory()->create();
        $payment = $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);

        $refund = $this->service()->refund($payment, 400.00, $staff, 'Court flooded');

        $this->assertSame('400.00', $refund->amount);
        $this->assertSame(PaymentStatus::REFUNDED, $payment->fresh()->status);
    }

    public function test_a_partial_refund_leaves_the_rest(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);

        $this->service()->refund($payment, 150.00, null, 'Late cancellation');
        $fresh = $payment->fresh();

        $this->assertSame(PaymentStatus::PARTIALLY_REFUNDED, $fresh->status);
        $this->assertSame('150.00', $fresh->refunded_amount);
        $this->assertSame(250.00, $fresh->refundableAmount());
    }

    public function test_refunds_cannot_exceed_what_was_collected(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);
        $this->service()->refund($payment, 300.00);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot refund more than was collected');

        $this->service()->refund($payment, 200.00);
    }

    public function test_a_second_refund_of_the_remainder_still_cannot_overdraw(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);
        $this->service()->refund($payment, 250.00);

        $this->expectException(RuntimeException::class);
        $this->service()->refund($payment, 250.00);
    }

    public function test_an_unpaid_payment_cannot_be_refunded(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::GCASH, 400.00);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be refunded');

        $this->service()->refund($payment, 100.00);
    }

    public function test_only_the_last_four_card_digits_are_stored(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::CARD, 400.00, [
            'card_last_four' => '4242',
        ]);

        $this->assertSame('4242', $payment->card_last_four);
    }

    public function test_a_full_card_number_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only the last four digits');

        $this->service()->record($this->booking, PaymentMethod::CARD, 400.00, [
            'card_last_four' => '4242424242424242',
        ]);
    }

    public function test_a_receipt_is_issued_for_a_settled_payment(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);

        $receipt = $this->service()->issueReceipt($payment, [
            ['description' => 'Court booking', 'amount' => 400.00],
        ]);

        $this->assertSame('400.00', $receipt->total);
        $this->assertSame('₱400.00', $receipt->formattedTotal());
        $this->assertCount(1, $receipt->line_items);
    }

    public function test_a_receipt_cannot_be_issued_for_an_unpaid_booking(): void
    {
        $payment = $this->service()->record($this->booking, PaymentMethod::GCASH, 400.00);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only be issued for a settled payment');

        $this->service()->issueReceipt($payment);
    }

    public function test_a_payment_is_scoped_to_its_organization(): void
    {
        $this->service()->record($this->booking, PaymentMethod::CASH, 400.00);

        // A staff member of a different facility must see none of it.
        $otherOrg = Organization::factory()->create();
        $otherStaff = User::factory()->forTenant($otherOrg, Role::FACILITY_OWNER)->create();

        $this->actingAs($otherStaff);

        $this->assertSame(0, Payment::query()->count(), 'Another tenant must not see this payment.');
    }
}

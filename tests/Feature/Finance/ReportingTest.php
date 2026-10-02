<?php

namespace Tests\Feature\Finance;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\MembershipPlan;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Memberships\MembershipService;
use App\Services\Payments\PaymentService;
use App\Services\Pos\SaleService;
use App\Services\Reports\ReportingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 12 acceptance checks: operational finance and reporting
 * (plan.md §20).
 */
class ReportingTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected Court $court;

    protected ReportingService $reports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->for($this->organization)->create();
        $this->court = Court::factory()->for($this->organization)->for($this->branch)->create();

        $this->reports = app(ReportingService::class);
    }

    protected function windowStart(): Carbon
    {
        return Carbon::parse('2026-03-01');
    }

    protected function windowEnd(): Carbon
    {
        return Carbon::parse('2026-03-31 23:59:59');
    }

    /**
     * A paid booking, which is the unit of booking revenue.
     */
    protected function paidBooking(string $amount = '1000.00', string $paidAt = '2026-03-10 18:00:00'): Booking
    {
        $booking = Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'customer_id' => Customer::factory()->create(['organization_id' => $this->organization->id])->id,
            'status' => BookingStatus::COMPLETED,
            'amount' => $amount,
        ]);

        $payment = app(PaymentService::class)->record($booking, PaymentMethod::GCASH, (float) $amount);

        // created_at/paid_at are guarded, so backdate them with a direct write.
        $booking->forceFill(['starts_at' => $paidAt])->save();

        DB::table('payments')->where('id', $payment->id)->update([
            'paid_at' => $paidAt,
            'status' => 'paid',
        ]);

        return $booking;
    }

    public function test_booking_revenue_is_counted(): void
    {
        $this->paidBooking('1000.00');
        $this->paidBooking('500.00', '2026-03-11 18:00:00');

        $this->assertSame(1500.0, $this->reports->bookingRevenue($this->windowStart(), $this->windowEnd()));
    }

    public function test_revenue_outside_the_window_is_ignored(): void
    {
        $this->paidBooking('1000.00', '2026-01-05 18:00:00');

        $this->assertSame(0.0, $this->reports->bookingRevenue($this->windowStart(), $this->windowEnd()));
    }

    public function test_refunds_reduce_revenue(): void
    {
        $booking = $this->paidBooking('1000.00');
        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();

        app(PaymentService::class)->refund($payment, 250.00);

        $this->assertSame(750.0, $this->reports->bookingRevenue($this->windowStart(), $this->windowEnd()));
    }

    public function test_an_unpaid_booking_is_not_revenue(): void
    {
        Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'status' => BookingStatus::CONFIRMED,
            'amount' => '1000.00',
        ]);

        $this->assertSame(0.0, $this->reports->bookingRevenue($this->windowStart(), $this->windowEnd()));
    }

    public function test_revenue_by_source_splits_correctly(): void
    {
        $this->paidBooking('1000.00');

        $sale = app(SaleService::class)->open($this->branch);
        $product = Product::factory()->create([
            'organization_id' => $this->organization->id,
            'price' => '250.00',
            'stock' => 10,
        ]);
        app(SaleService::class)->addItem($sale, $product, 2);
        $paid = app(SaleService::class)->checkout($sale);
        DB::table('sales')->where('id', $paid->id)->update([
            'paid_at' => '2026-03-12 10:00:00',
        ]);

        $plan = MembershipPlan::factory()->create([
            'organization_id' => $this->organization->id,
            'price' => '3000.00',
        ]);
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);
        $subscription = app(MembershipService::class)->subscribe($customer, $plan);
        // created_at is guarded, so backdate it through a direct write.
        DB::table('membership_subscriptions')
            ->where('id', $subscription->id)
            ->update(['created_at' => '2026-03-05 09:00:00']);

        $sources = $this->reports->revenueBySource($this->windowStart(), $this->windowEnd());

        $this->assertSame(1000.0, $sources['bookings']);
        $this->assertSame(500.0, $sources['products']);
        $this->assertSame(3000.0, $sources['memberships']);
    }

    public function test_expenses_are_summed_and_grouped(): void
    {
        Expense::factory()->create([
            'organization_id' => $this->organization->id,
            'category' => 'rent',
            'amount' => '20000.00',
            'incurred_on' => '2026-03-01',
        ]);
        Expense::factory()->create([
            'organization_id' => $this->organization->id,
            'category' => 'utilities',
            'amount' => '5000.00',
            'incurred_on' => '2026-03-02',
        ]);
        Expense::factory()->create([
            'organization_id' => $this->organization->id,
            'category' => 'rent',
            'amount' => '1000.00',
            'incurred_on' => '2026-03-03',
        ]);

        $this->assertSame(26000.0, $this->reports->totalExpenses($this->windowStart(), $this->windowEnd()));

        $byCategory = $this->reports->expensesByCategory($this->windowStart(), $this->windowEnd());

        $this->assertSame(21000.0, $byCategory['rent'], 'Rent must combine both entries.');
        $this->assertSame('rent', array_key_first($byCategory), 'Largest category comes first.');
    }

    public function test_profit_is_revenue_less_expenses(): void
    {
        $this->paidBooking('10000.00');
        Expense::factory()->create([
            'organization_id' => $this->organization->id,
            'amount' => '3000.00',
            'incurred_on' => '2026-03-05',
        ]);

        $this->assertSame(7000.0, $this->reports->profit($this->windowStart(), $this->windowEnd()));
    }

    public function test_revenue_by_branch_isolates_each_branch(): void
    {
        $other = Branch::factory()->for($this->organization)->create();
        $otherCourt = Court::factory()->for($this->organization)->for($other)->create();

        $this->paidBooking('1000.00');

        $otherBooking = Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $other->id,
            'court_id' => $otherCourt->id,
            'customer_id' => Customer::factory()->create(['organization_id' => $this->organization->id])->id,
            'status' => BookingStatus::COMPLETED,
            'amount' => '3000.00',
        ]);
        $payment = app(PaymentService::class)->record($otherBooking, PaymentMethod::CASH, 3000.0);
        DB::table('payments')->where('id', $payment->id)->update([
            'paid_at' => '2026-03-10 18:00:00',
            'status' => 'paid',
        ]);

        $byBranch = $this->reports->revenueByBranch($this->windowStart(), $this->windowEnd());

        $this->assertSame(3000.0, $byBranch[$other->name]);
        $this->assertSame(1000.0, $byBranch[$this->branch->name]);
    }

    public function test_revenue_by_payment_method(): void
    {
        $this->paidBooking('1000.00');

        $maya = Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'status' => BookingStatus::COMPLETED,
            'amount' => '500.00',
        ]);
        $payment = app(PaymentService::class)->record($maya, PaymentMethod::MAYA, 500.0);
        DB::table('payments')->where('id', $payment->id)->update([
            'paid_at' => '2026-03-12 10:00:00',
            'status' => 'paid',
        ]);

        $byMethod = $this->reports->revenueByPaymentMethod($this->windowStart(), $this->windowEnd());

        $this->assertSame(1000.0, $byMethod['gcash']);
        $this->assertSame(500.0, $byMethod['maya']);
    }

    public function test_utilisation_counts_only_played_bookings(): void
    {
        Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'status' => BookingStatus::COMPLETED,
            'duration_minutes' => 120,
            'starts_at' => '2026-03-10 18:00:00',
        ]);

        // A cancelled slot must not make the court look busy.
        Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'status' => BookingStatus::CANCELLED,
            'duration_minutes' => 120,
            'starts_at' => '2026-03-11 18:00:00',
        ]);

        $utilisation = $this->reports->courtUtilisation($this->windowStart(), $this->windowEnd());

        // 31 days x 14 bookable hours = 26,040 minutes available; 120 used.
        $this->assertSame(0.5, $utilisation[$this->court->name]);
    }

    public function test_outstanding_amounts_exclude_paid_bookings(): void
    {
        $this->paidBooking('1000.00');

        Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'customer_id' => Customer::factory()->create(['organization_id' => $this->organization->id])->id,
            'status' => BookingStatus::COMPLETED,
            'amount' => '700.00',
            'starts_at' => '2026-03-15 18:00:00',
        ]);

        $this->assertSame(700.0, $this->reports->outstandingAmounts($this->windowStart(), $this->windowEnd()));
    }

    public function test_kpis_are_computed(): void
    {
        $this->paidBooking('1000.00');
        $this->paidBooking('500.00', '2026-03-11 18:00:00');

        $kpis = $this->reports->kpis($this->windowStart(), $this->windowEnd());

        $this->assertSame(1500.0, $kpis['revenue']);
        $this->assertSame(2, $kpis['bookings']);
        $this->assertSame(750.0, $kpis['average_booking_value']);
    }

    public function test_kpis_are_safe_with_no_data(): void
    {
        $kpis = $this->reports->kpis($this->windowStart(), $this->windowEnd());

        $this->assertSame(0, $kpis['bookings']);
        $this->assertSame(0.0, $kpis['average_booking_value'], 'No division by zero.');
    }

    public function test_the_summary_is_formatted_in_peso(): void
    {
        $this->paidBooking('1250.00');

        $summary = $this->reports->formattedSummary($this->windowStart(), $this->windowEnd());

        $this->assertSame('₱1,250.00', $summary['revenue']);
    }

    public function test_reports_never_include_another_tenant(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherBranch = Branch::factory()->for($otherOrg)->create();
        $otherCourt = Court::factory()->for($otherOrg)->for($otherBranch)->create();

        $otherBooking = Booking::factory()->create([
            'organization_id' => $otherOrg->id,
            'branch_id' => $otherBranch->id,
            'court_id' => $otherCourt->id,
            'status' => BookingStatus::COMPLETED,
            'amount' => '5000.00',
        ]);
        $payment = app(PaymentService::class)->record($otherBooking, PaymentMethod::CASH, 5000.0);
        DB::table('payments')->where('id', $payment->id)->update([
            'paid_at' => '2026-03-10 18:00:00',
            'status' => 'paid',
        ]);

        $this->paidBooking('1000.00');

        // Reports run in whatever context they are called from. In a console
        // or queue there is no signed-in user, so the global tenant scope is
        // deliberately inactive and a report would span every facility.
        // Staff therefore read reports while authenticated, and the scope
        // applies. This test proves that path.
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->forTenant($this->organization, Role::FACILITY_OWNER)->create();
        $this->actingAs($owner);

        $this->assertSame(
            1000.0,
            $this->reports->bookingRevenue($this->windowStart(), $this->windowEnd()),
            'Another facility\'s money must never appear in my report.',
        );
    }
}

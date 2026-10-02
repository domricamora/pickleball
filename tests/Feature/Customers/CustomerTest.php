<?php

namespace Tests\Feature\Customers;

use App\Enums\BookingStatus;
use App\Enums\CustomerSegment;
use App\Enums\PaymentMethod;
use App\Enums\PreferredPlayingTime;
use App\Enums\Role;
use App\Enums\SkillLevel;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 acceptance checks: the customer / player CRM (plan.md §14).
 */
class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->for($this->organization)->create();
        $this->court = Court::factory()->for($this->organization)->for($this->branch)->create();
    }

    protected function customer(array $attributes = []): Customer
    {
        return Customer::factory()->create([
            'organization_id' => $this->organization->id,
        ] + $attributes);
    }

    /**
     * Give a customer some history.
     */
    protected function book(Customer $customer, BookingStatus $status, string $daysAgo = '1', string $amount = '400.00'): Booking
    {
        $booking = Booking::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'court_id' => $this->court->id,
            'customer_id' => $customer->id,
            'status' => $status,
            'starts_at' => now()->subDays((int) $daysAgo),
            'amount' => $amount,
        ]);

        if ($status === BookingStatus::COMPLETED) {
            app(PaymentService::class)->record($booking, PaymentMethod::CASH, (float) $amount);
        }

        return $booking;
    }

    public function test_a_customer_records_its_profile(): void
    {
        $customer = $this->customer([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birthday' => '1990-06-15',
            'skill_level' => SkillLevel::ADVANCED->value,
            'preferred_playing_time' => PreferredPlayingTime::EVENING->value,
        ]);

        $this->assertSame('Juan Dela Cruz', $customer->fullName());
        $this->assertSame(SkillLevel::ADVANCED, $customer->skill_level);
        $this->assertSame(PreferredPlayingTime::EVENING, $customer->preferred_playing_time);
        $this->assertIsInt($customer->age());
    }

    public function test_a_customer_without_a_birthday_has_no_age(): void
    {
        $customer = $this->customer(['birthday' => null]);

        $this->assertNull($customer->age());
    }

    public function test_metrics_count_completed_bookings(): void
    {
        $customer = $this->customer();
        $this->book($customer, BookingStatus::COMPLETED);
        $this->book($customer, BookingStatus::COMPLETED);

        $metrics = $customer->metrics();

        $this->assertSame(2, $metrics['total_bookings']);
        $this->assertNotNull($metrics['last_visit']);
    }

    public function test_a_cancelled_booking_is_not_counted_as_a_visit(): void
    {
        $customer = $this->customer();
        $this->book($customer, BookingStatus::COMPLETED);
        $this->book($customer, BookingStatus::CANCELLED);

        $metrics = $customer->metrics();

        $this->assertSame(1, $metrics['total_bookings'], 'A cancelled slot is not a visit.');
        $this->assertSame(1, $metrics['cancelled']);
    }

    public function test_total_spending_is_net_of_refunds(): void
    {
        $customer = $this->customer();
        $booking = $this->book($customer, BookingStatus::COMPLETED, '1', '1000.00');

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        app(PaymentService::class)->refund($payment, 250.00, null, 'Partial');

        // 1000 collected, 250 back = 750 net.
        $this->assertSame(750.0, $customer->metrics()['total_spending']);
    }

    public function test_a_new_player_is_segmented_as_new(): void
    {
        $customer = $this->customer();

        $this->assertContains(CustomerSegment::NEW_PLAYER, $customer->segments());
    }

    public function test_a_recent_player_is_active(): void
    {
        $customer = $this->customer();
        $this->book($customer, BookingStatus::COMPLETED, '5');

        $this->assertContains(CustomerSegment::ACTIVE, $customer->segments());
        $this->assertNotContains(CustomerSegment::NEW_PLAYER, $customer->segments());
    }

    public function test_a_long_absent_player_is_inactive(): void
    {
        $customer = $this->customer();
        $this->book($customer, BookingStatus::COMPLETED, '200');

        $segments = $customer->segments();

        $this->assertContains(CustomerSegment::INACTIVE, $segments);
        $this->assertNotContains(CustomerSegment::ACTIVE, $segments);
    }

    public function test_a_vip_is_flagged(): void
    {
        $customer = $this->customer(['is_vip' => true]);

        $this->assertContains(CustomerSegment::VIP, $customer->segments());
    }

    public function test_a_frequent_renter_is_identified(): void
    {
        $customer = $this->customer();

        for ($i = 0; $i < 6; $i++) {
            $this->book($customer, BookingStatus::COMPLETED, (string) ($i + 1));
        }

        $this->assertContains(CustomerSegment::FREQUENT_RENTER, $customer->segments());
    }

    public function test_segments_can_overlap(): void
    {
        $customer = $this->customer(['is_vip' => true]);

        for ($i = 0; $i < 6; $i++) {
            $this->book($customer, BookingStatus::COMPLETED, (string) ($i + 1));
        }

        $segments = $customer->segments();

        // A VIP who also plays constantly is both, and that is more useful
        // than forcing a single label.
        $this->assertContains(CustomerSegment::VIP, $segments);
        $this->assertContains(CustomerSegment::FREQUENT_RENTER, $segments);
        $this->assertContains(CustomerSegment::ACTIVE, $segments);
    }

    public function test_favorite_branch_and_court_are_reported(): void
    {
        $customer = $this->customer();
        $this->book($customer, BookingStatus::COMPLETED);
        $this->book($customer, BookingStatus::COMPLETED);

        $metrics = $customer->metrics();

        $this->assertSame($this->branch->name, $metrics['favorite_branch']);
        $this->assertSame($this->court->name, $metrics['favorite_court']);
    }

    public function test_search_finds_a_customer_by_name_and_mobile(): void
    {
        $customer = $this->customer([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'mobile' => '09171234567',
        ]);

        $this->assertTrue(Customer::search('Santos')->whereKey($customer->id)->exists());
        $this->assertTrue(Customer::search('0917')->whereKey($customer->id)->exists());
        $this->assertFalse(Customer::search('Nobody')->whereKey($customer->id)->exists());
    }

    public function test_an_empty_search_returns_everything(): void
    {
        $this->customer();

        $this->assertSame(1, Customer::search('  ')->count());
    }

    public function test_customers_are_isolated_between_tenants(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $mine = $this->customer();

        $otherOrg = Organization::factory()->create();
        $theirs = Customer::factory()->create(['organization_id' => $otherOrg->id]);

        // A staff member of my facility sees only my customers.
        $myStaff = User::factory()->forTenant($this->organization, Role::FACILITY_OWNER)->create();
        $this->actingAs($myStaff);

        $this->assertTrue(Customer::query()->whereKey($mine->id)->exists());
        $this->assertFalse(
            Customer::query()->whereKey($theirs->id)->exists(),
            'Another facility\'s customer must not be visible.',
        );
    }

    public function test_staff_of_another_tenant_see_no_customers(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->customer();

        $otherOrg = Organization::factory()->create();
        $otherStaff = User::factory()->forTenant($otherOrg, Role::FACILITY_OWNER)->create();

        $this->actingAs($otherStaff);

        $this->assertSame(0, Customer::query()->count());
    }
}

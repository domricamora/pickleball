<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The operator dashboard: real figures, scoped to the caller's tenant.
 */
class OperatorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected Court $court;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create(['name' => 'Makati Courts']);
        $this->branch = Branch::factory()->for($this->organization)->create(['name' => 'Main Branch']);
        $this->court = Court::factory()->for($this->organization)->for($this->branch)->create(['name' => 'Court A']);
        $this->owner = User::factory()->forTenant($this->organization, Role::FACILITY_OWNER)->create();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_owner_sees_the_operator_dashboard(): void
    {
        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Dashboard')
                ->has('kpis.revenue')
                ->has('kpis.bookings')
                ->has('revenue_by_source', 3)
                ->has('todays_bookings')
                ->has('utilisation')
                ->has('branches', 1)
                ->where('range', 30),
            );
    }

    public function test_kpis_are_formatted_as_pesos(): void
    {
        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->where('kpis.revenue', '₱0.00')
                ->where('kpis.average_booking_value', '₱0.00'),
            );
    }

    public function test_todays_bookings_are_listed_soonest_first(): void
    {
        $customer = Customer::factory()->for($this->organization)->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);

        $later = Booking::factory()->for($this->organization)->for($this->branch)
            ->for($this->court)->for($customer)->create([
                'status' => BookingStatus::CONFIRMED,
                'starts_at' => now()->setTime(18, 0),
                'ends_at' => now()->setTime(19, 0),
            ]);

        $earlier = Booking::factory()->for($this->organization)->for($this->branch)
            ->for($this->court)->for($customer)->create([
                'status' => BookingStatus::PENDING,
                'starts_at' => now()->setTime(8, 0),
                'ends_at' => now()->setTime(9, 0),
            ]);

        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->has('todays_bookings', 2)
                ->where('todays_bookings.0.id', $earlier->id)
                ->where('todays_bookings.1.id', $later->id)
                ->where('todays_bookings.0.player', 'Juan Dela Cruz')
                ->where('todays_bookings.0.court', 'Court A')
                ->where('todays_bookings.0.status.label', 'Pending'),
            );
    }

    public function test_cancelled_bookings_are_not_on_the_schedule(): void
    {
        Booking::factory()->for($this->organization)->for($this->branch)
            ->for($this->court)->create([
                'status' => BookingStatus::CANCELLED,
                'starts_at' => now()->setTime(10, 0),
                'ends_at' => now()->setTime(11, 0),
            ]);

        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertInertia(fn ($page) => $page->has('todays_bookings', 0));
    }

    public function test_paid_bookings_reach_the_revenue_figure(): void
    {
        $booking = Booking::factory()->for($this->organization)->for($this->branch)
            ->for($this->court)->create([
                'status' => BookingStatus::COMPLETED,
                'amount' => 1500,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->subDays(2)->addHour(),
            ]);

        // forBooking carries the booking's organization and branch across;
        // a bare for() would leave the payment in a different tenant, where
        // the scope would correctly hide it from this owner's dashboard.
        Payment::factory()
            ->forBooking($booking, '1500.00')
            ->paid()
            ->create(['paid_at' => now()->subDays(2)]);

        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->where('kpis.revenue', '₱1,500.00')
                ->where('kpis.bookings', 1),
            );
    }

    public function test_another_tenants_bookings_are_invisible(): void
    {
        $rival = Organization::factory()->create();
        $rivalBranch = Branch::factory()->for($rival)->create();
        $rivalCourt = Court::factory()->for($rival)->for($rivalBranch)->create();

        Booking::factory()->for($rival)->for($rivalBranch)->for($rivalCourt)->create([
            'status' => BookingStatus::CONFIRMED,
            'starts_at' => now()->setTime(14, 0),
            'ends_at' => now()->setTime(15, 0),
        ]);

        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->has('todays_bookings', 0)
                ->has('branches', 1),
            );
    }

    public function test_range_is_validated_against_the_allowed_windows(): void
    {
        $this->actingAs($this->owner)
            ->get('/admin?range=7')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('range', 7));

        $this->actingAs($this->owner)
            ->get('/admin?range=999')
            ->assertSessionHasErrors('range');
    }

    public function test_a_player_cannot_reach_the_operator_dashboard(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get('/admin')
            ->assertForbidden();
    }
}

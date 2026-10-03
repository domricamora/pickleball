<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Sale;
use App\Models\Staff;
use App\Models\User;
use App\Services\Reports\ReportingService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Demo data must look like a club that has been trading, without breaking the
 * rules the real application enforces.
 *
 * The checks that matter: no double-booked court, every booking backed by the
 * payment the reports read, and tenant scoping on every row. A seeder that
 * violates them leaves the booking engine and the reports disagreeing.
 *
 * Counts come from the query builder rather than Eloquent, because the tenant
 * global scope deliberately hides rows when nobody is signed in -- these
 * assertions are about what the seeder wrote, not what a viewer may see.
 */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $organization = Organization::create([
            'name' => 'Test Pickle Club',
            'status' => 'active',
            'timezone' => 'Asia/Manila',
            'currency' => 'PHP',
        ]);

        $branch = Branch::create([
            'organization_id' => $organization->id,
            'name' => 'Main Branch',
            'is_primary' => true,
            'status' => 'active',
        ]);

        foreach (range(1, 3) as $index) {
            // CourtFactory already knows the valid status and surface values.
            Court::factory()->create([
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'name' => 'Court '.$index,
            ]);
        }

        // forceFill: organization_id and branch_id are deliberately absent from
        // the fillable list, because a tenant membership is only ever set by an
        // Action and never by request data (see App\Models\User).
        $user = (new User)->forceFill([
            'name' => 'Test Owner',
            'email' => 'owner@example.test',
            'password' => bcrypt('TestPassw0rd!'),
            'email_verified_at' => now(),
            'is_active' => true,
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);
        $user->save();
        $user->syncRoles([Role::FACILITY_OWNER->value]);

        $this->seed(DemoDataSeeder::class);
    }

    public function test_it_fills_the_operational_tables(): void
    {
        foreach (['customers', 'staff', 'bookings', 'payments', 'products', 'sales', 'events'] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "{$table} was left empty.");
        }
    }

    public function test_no_two_bookings_share_a_court_and_time(): void
    {
        $clashes = 0;
        $previous = null;

        foreach (DB::table('bookings')->orderBy('court_id')->orderBy('starts_at')->get() as $booking) {
            if ($previous !== null
                && $previous->court_id === $booking->court_id
                && $booking->starts_at < $previous->ends_at) {
                $clashes++;
            }

            $previous = $booking;
        }

        $this->assertSame(
            0,
            $clashes,
            'The booking engine refuses overlapping slots (plan.md §31), so demo data must contain none.'
        );
    }

    public function test_every_booking_has_a_payment_row(): void
    {
        $this->assertSame(
            DB::table('bookings')->count(),
            DB::table('payments')->count(),
            'ReportingService sums payments, not booking amounts, so each booking needs a payment.'
        );
    }

    public function test_every_seeded_row_carries_the_tenant(): void
    {
        $orgId = Organization::first()->id;

        foreach ([Booking::class, Customer::class, Staff::class, Expense::class, Sale::class, Court::class] as $model) {
            $orphans = $model::withoutGlobalScope('organization')
                ->where(function ($query) use ($orgId): void {
                    $query->whereNull('organization_id')->orWhere('organization_id', '!=', $orgId);
                })
                ->count();

            $this->assertSame(0, $orphans, $model.' has rows outside the tenant.');
        }
    }

    public function test_prices_and_opening_hours_cover_every_court(): void
    {
        $orgId = Organization::first()->id;
        $courts = DB::table('courts')->count();

        // PriceResolver::candidates() filters on branch_id, so a price without
        // one would never match and every slot would quote zero.
        $this->assertSame(
            0,
            DB::table('court_prices')->where('organization_id', $orgId)->whereNull('branch_id')->count(),
            'Prices need a branch_id or PriceResolver cannot match them.'
        );

        $this->assertGreaterThanOrEqual(
            $courts,
            DB::table('court_prices')->where('organization_id', $orgId)->count(),
            'Every court needs at least one published rate.'
        );

        $this->assertSame(
            $courts * 7,
            DB::table('court_schedules')->where('organization_id', $orgId)->count(),
            'Every court needs a schedule for each weekday.'
        );
    }

    public function test_the_reports_can_actually_read_the_data(): void
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $reports = app(ReportingService::class);
        $kpis = $reports->kpis($from, $to);

        $this->assertGreaterThan(0, $kpis['bookings'], 'The 30-day window should not be empty.');
        $this->assertGreaterThan(0, $kpis['revenue'], 'Revenue should be non-zero in the default window.');

        // All three sources populated, so the revenue split renders fully.
        foreach ($reports->revenueBySource($from, $to) as $source => $amount) {
            $this->assertGreaterThan(0, $amount, "Revenue source '{$source}' is empty.");
        }
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $count = fn (): array => [
            'bookings' => DB::table('bookings')->count(),
            'customers' => DB::table('customers')->count(),
            'sales' => DB::table('sales')->count(),
        ];

        $before = $count();
        $this->seed(DemoDataSeeder::class);

        $this->assertSame($before, $count(), 'Re-running must replace demo data, not duplicate it.');
    }
}

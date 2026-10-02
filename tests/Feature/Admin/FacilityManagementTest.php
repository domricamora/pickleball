<?php

namespace Tests\Feature\Admin;

use App\Enums\CourtStatus;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 acceptance checks: facility and court management (plan.md §11).
 */
class FacilityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create(['name' => 'Makati Courts']);
        $this->branch = Branch::factory()->for($this->organization)->create(['name' => 'Main Branch']);
        $this->owner = User::factory()->forTenant($this->organization, Role::FACILITY_OWNER)->create();
    }

    /** Minimal valid payload for creating a court. */
    protected function courtPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch->id,
            'name' => 'Court A',
            'number' => 'A1',
            'surface' => 'acrylic',
            'type' => 'tournament',
            'setting' => 'indoor',
            'status' => 'available',
            'capacity' => 4,
        ], $overrides);
    }

    public function test_admin_pages_require_authentication(): void
    {
        $this->get('/admin/facilities')->assertRedirect('/login');
        $this->get('/admin/courts')->assertRedirect('/login');
    }

    public function test_owner_can_list_facilities_and_courts(): void
    {
        $this->actingAs($this->owner)
            ->get('/admin/facilities')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/facilities/Index')
                ->has('facilities', 1),
            );

        $this->actingAs($this->owner)
            ->get('/admin/courts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/courts/Index')->has('courts', 0));
    }

    public function test_owner_can_create_a_facility(): void
    {
        $this->actingAs($this->owner)
            ->post('/admin/facilities', [
                'name' => 'Cebu Courts',
                'status' => 'active',
                'address_barangay' => 'Mabini',
                'address_city' => 'Cebu City',
                'address_province' => 'Cebu',
                'address_region' => 'Region VII',
                'address_postal_code' => '6000',
                'phone' => '+63 32 123 4567',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'name' => 'Cebu Courts',
            // The tenant comes from the session, never the request body.
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_owner_can_create_a_court(): void
    {
        $this->actingAs($this->owner)
            ->post('/admin/courts', $this->courtPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('courts', [
            'name' => 'Court A',
            'branch_id' => $this->branch->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_court_number_must_be_unique_within_a_facility(): void
    {
        Court::factory()->for($this->organization)->for($this->branch)->create([
            'name' => 'Court A',
            'number' => 'A1',
        ]);

        $this->actingAs($this->owner)
            ->post('/admin/courts', $this->courtPayload(['name' => 'Court B']))
            ->assertSessionHasErrors('number');
    }

    public function test_court_cannot_be_created_against_another_tenants_branch(): void
    {
        $rival = Organization::factory()->create();
        $rivalBranch = Branch::factory()->for($rival)->create();

        $this->actingAs($this->owner)
            ->post('/admin/courts', $this->courtPayload([
                'branch_id' => $rivalBranch->id,
                'name' => 'Sneaky Court',
            ]))
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('courts', ['name' => 'Sneaky Court']);
    }

    public function test_court_from_another_tenant_is_not_reachable(): void
    {
        $rival = Organization::factory()->create();
        $rivalCourt = Court::factory()->for($rival)->create();

        // The global scope means the route model cannot even resolve it.
        $this->actingAs($this->owner)->get("/admin/courts/{$rivalCourt->id}")->assertNotFound();
        $this->actingAs($this->owner)->get("/admin/courts/{$rivalCourt->id}/edit")->assertNotFound();

        $this->actingAs($this->owner)
            ->put("/admin/courts/{$rivalCourt->id}", $this->courtPayload(['name' => 'Hijacked']))
            ->assertNotFound();

        $this->actingAs($this->owner)->delete("/admin/courts/{$rivalCourt->id}")->assertNotFound();

        $this->assertDatabaseHas('courts', ['id' => $rivalCourt->id, 'name' => $rivalCourt->name]);
    }

    public function test_customer_cannot_reach_the_admin_area(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::CUSTOMER->value);

        $this->actingAs($customer)->get('/admin/courts')->assertForbidden();
        $this->actingAs($customer)->get('/admin/facilities')->assertForbidden();
    }

    public function test_cashier_can_view_but_not_manage_courts(): void
    {
        $cashier = User::factory()->forTenant($this->organization, Role::CASHIER)->create();

        $this->actingAs($cashier)->get('/admin/courts')->assertOk();

        $this->actingAs($cashier)
            ->post('/admin/courts', $this->courtPayload(['name' => 'Not Allowed']))
            ->assertForbidden();

        $this->assertDatabaseMissing('courts', ['name' => 'Not Allowed']);
    }

    public function test_super_admin_can_manage_across_tenants(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/courts')->assertOk();

        $this->actingAs($admin)
            ->post('/admin/courts', $this->courtPayload(['name' => 'Admin Created Court']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('courts', ['name' => 'Admin Created Court']);
    }

    public function test_owner_can_update_and_soft_delete_a_court(): void
    {
        $court = Court::factory()->for($this->organization)->for($this->branch)->create([
            'name' => 'Court A',
            'status' => CourtStatus::AVAILABLE->value,
        ]);

        $this->actingAs($this->owner)
            ->put("/admin/courts/{$court->id}", $this->courtPayload([
                'name' => 'Court A Renamed',
                'status' => 'maintenance',
                'capacity' => 6,
            ]))
            ->assertSessionHasNoErrors();

        $court->refresh();
        $this->assertSame('Court A Renamed', $court->name);
        $this->assertSame(CourtStatus::MAINTENANCE, $court->status);
        $this->assertFalse($court->isBookable());

        $this->actingAs($this->owner)->delete("/admin/courts/{$court->id}")->assertRedirect();

        // Soft deleted: the row survives for historical bookings.
        $this->assertSoftDeleted('courts', ['id' => $court->id]);
    }

    public function test_court_show_page_lists_schedule_blocks_and_prices(): void
    {
        $court = Court::factory()->for($this->organization)->for($this->branch)->create();

        $this->actingAs($this->owner)
            ->get("/admin/courts/{$court->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/courts/Show')
                ->has('court.schedules')
                ->has('court.blocks')
                ->has('court.prices')
                ->has('weekdays', 7),
            );
    }

    public function test_court_list_can_be_filtered_by_branch(): void
    {
        $other = Branch::factory()->for($this->organization)->create(['name' => 'North Branch']);

        Court::factory()->for($this->organization)->for($this->branch)->create(['name' => 'Main Court']);
        Court::factory()->for($this->organization)->for($other)->create(['name' => 'North Court']);

        $this->actingAs($this->owner)
            ->get("/admin/courts?branch_id={$other->id}")
            ->assertInertia(fn ($page) => $page
                ->has('courts', 1)
                ->where('courts.0.name', 'North Court')
                ->where('filters.branch_id', $other->id),
            );
    }

    public function test_owner_can_open_the_create_forms(): void
    {
        $this->actingAs($this->owner)
            ->get('/admin/courts/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/courts/Create')->has('branches', 1));

        $this->actingAs($this->owner)
            ->get('/admin/facilities/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/facilities/Create')->has('regions'));
    }
}

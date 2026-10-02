<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tenant isolation (plan.md §6, §28).
 *
 * The global scope on BelongsToOrganization must make cross-tenant reads
 * impossible even when a query forgets to filter explicitly.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_branch_queries_are_scoped_to_the_signed_in_tenant(): void
    {
        $makati = Organization::factory()->create(['name' => 'Makati Courts']);
        $cebu = Organization::factory()->create(['name' => 'Cebu Courts']);

        Branch::factory()->for($makati)->create(['name' => 'Makati Branch']);
        Branch::factory()->for($cebu)->create(['name' => 'Cebu Branch']);

        $staff = User::factory()->forTenant($makati, Role::MANAGER)->create();

        $this->actingAs($staff);

        $names = Branch::query()->pluck('name');

        $this->assertTrue($names->contains('Makati Branch'));
        $this->assertFalse(
            $names->contains('Cebu Branch'),
            'A tenant must never see another tenant\'s branches.'
        );
    }

    public function test_platform_staff_sees_every_tenant(): void
    {
        $makati = Organization::factory()->create();
        $cebu = Organization::factory()->create();

        Branch::factory()->for($makati)->create();
        Branch::factory()->for($cebu)->create();

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin);

        $this->assertSame(2, Branch::query()->count());
    }

    public function test_customer_without_a_tenant_sees_no_branches(): void
    {
        $organization = Organization::factory()->create();
        Branch::factory()->for($organization)->create();

        $player = User::factory()->create();
        $player->assignRole(Role::CUSTOMER->value);

        $this->actingAs($player);

        $this->assertSame(0, Branch::query()->count());
    }

    public function test_a_branch_cannot_be_read_across_tenants(): void
    {
        $makati = Organization::factory()->create();
        $branch = Branch::factory()->for($makati)->create();

        $other = Organization::factory()->create();
        $intruder = User::factory()->forTenant($other, Role::FACILITY_OWNER)->create();

        $this->actingAs($intruder);

        $this->assertNull(
            Branch::query()->find($branch->id),
            'Finding a branch by id must still respect tenant scope.'
        );
    }

    public function test_console_and_queues_are_not_tenant_restricted(): void
    {
        $organization = Organization::factory()->create();
        Branch::factory()->for($organization)->create();

        // No authenticated user in a console/test context, so the scope must
        // not silently hide rows from jobs, seeders or installers.
        $this->assertSame(1, Branch::query()->count());
    }
}

<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the roles and permissions declared in App\Enums\Role.
 *
 * Idempotent: safe to run on every deploy. Role and permission names come from
 * the enum so a new role only has to be described in one place (plan.md §7).
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // Super Admin is granted everything, so it holds no explicit rows.
        SpatieRole::findOrCreate(Role::SUPER_ADMIN->value, $guard);

        $names = [];

        foreach (Role::tenantRoles() as $role) {
            $permissions = $role->permissions();

            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, $guard);
                $names[] = $permission;
            }

            $model = SpatieRole::findOrCreate($role->value, $guard);
            $model->syncPermissions($permissions);
        }

        $this->command->info(sprintf(
            'Created %d roles and %d permissions.',
            count(Role::cases()),
            count(array_unique($names)),
        ));
    }
}

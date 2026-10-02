<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Roles and permissions only.
     *
     * Tenants, branches and staff accounts are created by the installer
     * command so no credentials ever live in the repository (plan.md §2).
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}

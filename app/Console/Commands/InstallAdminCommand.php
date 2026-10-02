<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first administrator from environment variables.
 *
 * Credentials are read from the environment and never hard-coded, never
 * committed and never echoed back in full (plan.md §2, §10).
 */
class InstallAdminCommand extends Command
{
    protected $signature = 'app:install-admin
                            {--name= : Administrator display name}
                            {--email= : Administrator email (falls back to ADMIN_EMAIL)}
                            {--password= : Administrator password (falls back to ADMIN_PASSWORD)}
                            {--organization= : Tenant name (falls back to ADMIN_ORGANIZATION)}
                            {--force : Update the password if the account already exists}';

    protected $description = 'Create the initial platform administrator from environment variables';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: config('platform.admin.email'));
        $password = (string) ($this->option('password') ?: config('platform.admin.password'));
        $name = (string) ($this->option('name') ?: config('platform.admin.name'));
        $organizationName = (string) ($this->option('organization') ?: config('platform.admin.organization'));

        if ($email === '' || $password === '') {
            $this->components->error(
                'No administrator credentials supplied. Set ADMIN_EMAIL and ADMIN_PASSWORD in .env, '
                .'or pass --email and --password.'
            );

            return self::FAILURE;
        }

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'name' => $name !== '' ? $name : 'Administrator',
        ], [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->option('force')) {
            $this->components->error("A user with {$email} already exists. Re-run with --force to reset the password.");

            return self::FAILURE;
        }

        // Roles must exist before they can be assigned.
        $this->callSilent('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = $existing ?? new User;
        $user->name = $name !== '' ? $name : 'Administrator';
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->is_active = true;
        // Platform staff operate across tenants and belong to none.
        $user->organization_id = null;
        $user->branch_id = null;
        $user->save();

        $user->syncRoles([Role::SUPER_ADMIN->value]);

        // Optionally attach a first tenant so the owner has something to manage.
        $organization = null;

        if ($organizationName !== '') {
            $organization = Organization::firstOrCreate(
                ['name' => $organizationName],
                ['status' => 'active', 'timezone' => 'Asia/Manila', 'currency' => 'PHP'],
            );

            $organization->branches()->firstOrCreate(
                ['name' => 'Main Branch'],
                ['is_primary' => true, 'status' => 'active'],
            );

            $owner = $user->organizations()->where('organizations.id', $organization->id)->exists();

            if (! $owner) {
                $user->organizations()->attach($organization->id, [
                    'role' => Role::SUPER_ADMIN->value,
                    'is_primary' => true,
                    'joined_at' => now(),
                ]);
            }
        }

        $this->components->info("Administrator ready: {$user->email}");

        if ($organization instanceof Organization) {
            $branch = $organization->branches()->first();
            $this->components->info("Tenant: {$organization->name}".($branch ? " (branch: {$branch->name})" : ''));
        }

        $this->newLine();
        $this->components->warn('Change this password immediately after the first login (see plan.md, Security Rule).');

        return self::SUCCESS;
    }
}

<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => '+63 917 '.fake()->unique()->numerify('### ####'),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'competitive']),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * Attach the user to a tenant with the given role.
     */
    public function forTenant(Organization $organization, Role $role = Role::FACILITY_OWNER): static
    {
        return $this->state(fn (): array => [
            'organization_id' => $organization->id,
        ])->afterCreating(function (User $user) use ($organization, $role): void {
            $user->assignRole($role->value);
            $user->organizations()->attach($organization->id, [
                'role' => $role->value,
                'is_primary' => true,
                'joined_at' => now(),
            ]);
        });
    }

    /**
     * Platform staff with no tenant.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (): array => [
            'organization_id' => null,
        ])->afterCreating(function (User $user): void {
            $user->assignRole(Role::SUPER_ADMIN->value);
        });
    }
}

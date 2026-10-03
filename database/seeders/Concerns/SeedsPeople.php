<?php

namespace Database\Seeders\Concerns;

use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Support\Collection;

/**
 * The people a club actually has: a handful of players and a few employees.
 *
 * Staff get no password of their own -- a staff member may not have a login
 * (see App\Models\Staff), and no credential is ever written here (plan.md §2).
 */
trait SeedsPeople
{
    /**
     * Returns a Collection so callers can pluck() ids without reshaping.
     *
     * @return Collection<int, Customer>
     */
    private function seedCustomers(int $orgId): Collection
    {
        $players = [
            ['first_name' => 'Andrea', 'last_name' => 'Reyes', 'skill_level' => 'advanced', 'is_vip' => true],
            ['first_name' => 'Miguel', 'last_name' => 'Santos', 'skill_level' => 'competitive', 'is_vip' => true],
            ['first_name' => 'Katrina', 'last_name' => 'Bautista', 'skill_level' => 'intermediate', 'is_vip' => false],
            ['first_name' => 'Jomar', 'last_name' => 'Dela Cruz', 'skill_level' => 'beginner', 'is_vip' => false],
            ['first_name' => 'Bea', 'last_name' => 'Villanueva', 'skill_level' => 'advanced', 'is_vip' => false],
            ['first_name' => 'Carlo', 'last_name' => 'Mendoza', 'skill_level' => 'intermediate', 'is_vip' => false],
            ['first_name' => 'Nina', 'last_name' => 'Ocampo', 'skill_level' => 'competitive', 'is_vip' => true],
            ['first_name' => 'Paolo', 'last_name' => 'Garcia', 'skill_level' => 'beginner', 'is_vip' => false],
            ['first_name' => 'Grace', 'last_name' => 'Lim', 'skill_level' => 'advanced', 'is_vip' => false],
            ['first_name' => 'Rico', 'last_name' => 'Tan', 'skill_level' => 'intermediate', 'is_vip' => false],
            ['first_name' => 'Liza', 'last_name' => 'Roxas', 'skill_level' => 'competitive', 'is_vip' => false],
            ['first_name' => 'Mark', 'last_name' => 'Aquino', 'skill_level' => 'beginner', 'is_vip' => false],
        ];

        $barangays = ['Bel-Air', 'Poblacion', 'Salcedo', 'Legazpi', 'San Lorenzo', 'Guadalupe'];

        $customers = [];

        foreach ($players as $index => $player) {
            $slug = strtolower($player['first_name'].'.'.$player['last_name']);

            $customers[] = Customer::create([
                'organization_id' => $orgId,
                'first_name' => $player['first_name'],
                'last_name' => $player['last_name'],
                // Example domains keep demo mail out of anyone's real inbox.
                'email' => $slug.'@example.test',
                // Philippine mobile format: 09XX XXX XXXX.
                'mobile' => '09'.fake()->numerify('#########'),
                'birthday' => fake()->dateTimeBetween('-45 years', '-18 years')->format('Y-m-d'),
                'skill_level' => $player['skill_level'],
                'preferred_playing_time' => fake()->randomElement(['morning', 'afternoon', 'evening']),
                'address_barangay' => $barangays[$index % count($barangays)],
                'address_city' => 'Makati',
                'address_province' => 'Metro Manila',
                'consents_to_marketing' => $player['is_vip'],
                'consented_at' => now()->subDays(random_int(20, 200)),
                'is_vip' => $player['is_vip'],
                'is_active' => true,
            ]);
        }

        return collect($customers);
    }

    /**
     * Returns a Collection so callers can pluck() ids without reshaping.
     *
     * @return Collection<int, Staff>
     */
    private function seedStaff(int $orgId, int $branchId): Collection
    {
        $team = [
            ['first_name' => 'Rowena', 'last_name' => 'Navarro', 'role' => 'Manager', 'commission_rate' => 0.00, 'base_salary' => 45000.00],
            ['first_name' => 'Jayson', 'last_name' => 'Pineda', 'role' => 'Front Desk', 'commission_rate' => 0.00, 'base_salary' => 28000.00],
            ['first_name' => 'Tina', 'last_name' => 'Alonzo', 'role' => 'Cashier', 'commission_rate' => 2.00, 'base_salary' => 24000.00],
            ['first_name' => 'Ren', 'last_name' => 'Bautista', 'role' => 'Cashier', 'commission_rate' => 2.00, 'base_salary' => 24000.00],
            ['first_name' => 'Coach Dave', 'last_name' => 'Mabini', 'role' => 'Coach', 'commission_rate' => 15.00, 'base_salary' => 32000.00],
            ['first_name' => 'Ogie', 'last_name' => 'Salazar', 'role' => 'Staff', 'commission_rate' => 0.00, 'base_salary' => 22000.00],
        ];

        $staff = [];

        foreach ($team as $index => $member) {
            $staff[] = Staff::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'employee_number' => sprintf('PP-%04d', $index + 1),
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                // Philippine mobile format.
                'phone' => '09'.fake()->numerify('#########'),
                'hired_on' => now()->subDays(random_int(200, 900))->toDateString(),
                'role' => $member['role'],
                'commission_rate' => $member['commission_rate'],
                'base_salary' => $member['base_salary'],
                'is_active' => true,
            ]);
        }

        return collect($staff);
    }
}

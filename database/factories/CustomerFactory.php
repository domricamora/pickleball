<?php

namespace Database\Factories;

use App\Enums\PreferredPlayingTime;
use App\Enums\SkillLevel;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'organization_id' => Organization::factory(),
            'first_name' => $first,
            'last_name' => $last,
            // Philippine mobile format: 09XX XXX XXXX.
            'mobile' => '09'.fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'birthday' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'skill_level' => fake()->randomElement(SkillLevel::values()),
            'preferred_playing_time' => fake()->randomElement(PreferredPlayingTime::values()),
            'address_city' => fake()->randomElement(['Quezon City', 'Makati', 'Pasig', 'Taguig']),
            'is_active' => true,
        ];
    }

    public function vip(): static
    {
        return $this->state(fn (): array => ['is_vip' => true]);
    }
}

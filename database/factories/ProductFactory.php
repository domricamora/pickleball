<?php

namespace Database\Factories;

use App\Enums\ProductCategory;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement([
                'Pro Paddle',
                'Tourney Ball Pack',
                'Grip Wipes',
                'Water Bottle 1L',
                'Energy Bar',
                'Tournament Rental Set',
            ]),
            'category' => fake()->randomElement(ProductCategory::values()),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'price' => fake()->randomElement(['250.00', '450.00', '850.00', '1200.00']),
            'currency' => 'PHP',
            'stock' => 20,
            'reorder_level' => 5,
            'track_stock' => true,
            // Set explicitly: an omitted value becomes NULL in PHP and would
            // bypass the column default.
            'tax_rate' => '0.00',
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock' => 0]);
    }

    public function untracked(): static
    {
        return $this->state(fn (): array => ['track_stock' => false, 'stock' => 0]);
    }
}

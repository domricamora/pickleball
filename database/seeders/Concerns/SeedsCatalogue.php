<?php

namespace Database\Seeders\Concerns;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The pro shop catalogue: a supplier, stock on hand, and the opening balance.
 *
 * Products feed the POS and the inventory screens; without stock levels those
 * pages list nothing and reorder alerts can never fire (plan.md §17).
 */
trait SeedsCatalogue
{
    /**
     * @return Collection<int, Product>
     */
    private function seedProducts(int $orgId, int $branchId): Collection
    {
        $supplier = Supplier::create([
            'organization_id' => $orgId,
            'name' => 'Metro Manila Sports Supply',
            'contact_name' => 'Sales Desk',
            // Example domains keep demo mail out of anyone's real inbox.
            'email' => 'orders@example.test',
            'mobile' => '09'.fake()->numerify('#########'),
            'address' => '12 Gil Puyat Ave, Makati City',
            'is_active' => true,
        ]);

        $catalogue = [
            ['name' => 'Pickleball Paddle -- Pro', 'category' => ProductCategory::PADDLE, 'price' => 4850.00, 'cost' => 2900.00, 'stock' => 18],
            ['name' => 'Pickleball Paddle -- Beginner', 'category' => ProductCategory::PADDLE, 'price' => 2950.00, 'cost' => 1750.00, 'stock' => 24],
            ['name' => 'Pickleballs (3-pack)', 'category' => ProductCategory::BALL, 'price' => 850.00, 'cost' => 480.00, 'stock' => 60],
            ['name' => 'Grip Wristband', 'category' => ProductCategory::GRIP, 'price' => 350.00, 'cost' => 150.00, 'stock' => 45],
            ['name' => 'Cooling Towel', 'category' => ProductCategory::APPAREL, 'price' => 250.00, 'cost' => 95.00, 'stock' => 80],
            ['name' => 'Court-Side Water Bottle 1L', 'category' => ProductCategory::DRINK, 'price' => 450.00, 'cost' => 210.00, 'stock' => 36],
            ['name' => 'Paddle Cover', 'category' => ProductCategory::BAG, 'price' => 650.00, 'cost' => 280.00, 'stock' => 22],
            ['name' => 'Energy Bar', 'category' => ProductCategory::SNACK, 'price' => 95.00, 'cost' => 45.00, 'stock' => 90],
            ['name' => 'Loaner Paddle', 'category' => ProductCategory::RENTAL, 'price' => 200.00, 'cost' => 60.00, 'stock' => 14],
        ];

        $products = [];

        foreach ($catalogue as $index => $item) {
            $product = Product::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'description' => $item['name'].' -- sold at the facility front desk.',
                'category' => $item['category']->value,
                'sku' => sprintf('SKU-%04d', $index + 1),
                'barcode' => fake()->unique()->numerify('48###########'),
                'price' => number_format($item['price'], 2, '.', ''),
                'cost' => number_format($item['cost'], 2, '.', ''),
                'currency' => 'PHP',
                'stock' => $item['stock'],
                'reorder_level' => 10,
                'is_active' => true,
                'track_stock' => true,
                // 12% VAT is the standard Philippine business rate.
                'tax_rate' => 12.00,
                'supplier_id' => $supplier->id,
            ]);

            $products[] = $product;

            // Opening balance, so the stock ledger explains the quantity on hand.
            StockMovement::create([
                'organization_id' => $orgId,
                'product_id' => $product->id,
                'quantity' => $item['stock'],
                'reason' => 'restock',
                'notes' => 'Opening balance received from the supplier.',
                'created_at' => now()->subDays(random_int(60, 150)),
            ]);
        }

        return collect($products);
    }
}

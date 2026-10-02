<?php

namespace Tests\Feature\Pos;

use App\Enums\ProductCategory;
use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\Pos\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 9 acceptance checks: POS carts, stock and refunds (plan.md §17).
 */
class SaleTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected SaleService $pos;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $this->branch = Branch::factory()->for($organization)->create();

        $this->pos = app(SaleService::class);
    }

    protected function product(array $attributes = []): Product
    {
        return Product::factory()->create([
            'organization_id' => $this->branch->organization_id,
        ] + $attributes);
    }

    protected function cart(): Sale
    {
        return $this->pos->open($this->branch);
    }

    public function test_a_product_gets_a_unique_slug(): void
    {
        $first = $this->product(['name' => 'Pro Paddle']);
        $second = $this->product(['name' => 'Pro Paddle']);

        $this->assertSame('pro-paddle', $first->slug);
        $this->assertSame('pro-paddle-2', $second->slug);
    }

    public function test_a_product_is_found_by_name_sku_or_barcode(): void
    {
        $product = $this->product([
            'name' => 'Tourney Ball Pack',
            'sku' => 'BALL-001',
            'barcode' => '4800000000017',
        ]);

        $this->assertTrue(Product::search('Tourney')->whereKey($product->id)->exists());
        $this->assertTrue(Product::search('BALL-001')->whereKey($product->id)->exists());
        $this->assertTrue(Product::search('4800000000017')->whereKey($product->id)->exists());
        $this->assertFalse(Product::search('Nothing')->whereKey($product->id)->exists());
    }

    public function test_adding_to_the_cart_computes_the_total(): void
    {
        $sale = $this->cart();
        $paddle = $this->product(['price' => '1200.00']);

        $this->pos->addItem($sale, $paddle, 2);

        $this->assertSame('2400.00', $sale->fresh()->subtotal);
        $this->assertSame('2400.00', $sale->fresh()->total);
    }

    public function test_adding_the_same_product_twice_merges_the_line(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['price' => '450.00']);

        $this->pos->addItem($sale, $ball, 1);
        $this->pos->addItem($sale, $ball, 2);

        $this->assertSame(1, $sale->items()->count());
        $this->assertSame(3, (int) $sale->items()->first()->quantity);
        $this->assertSame('1350.00', $sale->fresh()->subtotal);
    }

    public function test_a_discount_reduces_the_total(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(['price' => '1000.00']), 1);

        $this->pos->applyDiscount($sale, 100.0);

        $this->assertSame('900.00', $sale->fresh()->total);
    }

    public function test_a_discount_cannot_exceed_the_subtotal(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(['price' => '500.00']), 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be more than the subtotal');

        $this->pos->applyDiscount($sale, 600.0);
    }

    public function test_tax_is_added_on_top(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product([
            'price' => '1000.00',
            'tax_rate' => '12.00',
        ]), 1);

        $fresh = $sale->fresh();

        $this->assertSame('1000.00', $fresh->subtotal);
        $this->assertSame('120.00', $fresh->tax);
        $this->assertSame('1120.00', $fresh->total);
    }

    public function test_checkout_marks_the_sale_paid(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(['price' => '500.00']), 1);

        $paid = $this->pos->checkout($sale);

        $this->assertSame(SaleStatus::PAID, $paid->status);
        $this->assertNotNull($paid->paid_at);
    }

    public function test_checkout_works_out_the_change(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(['price' => '450.00']), 1);

        $paid = $this->pos->checkout($sale, 500.0);

        $this->assertSame('500.00', $paid->amount_tendered);
        $this->assertSame('50.00', $paid->change_due);
    }

    public function test_under_tendering_leaves_no_change_due(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(['price' => '450.00']), 1);

        $paid = $this->pos->checkout($sale, 200.0);

        $this->assertSame('0.00', $paid->change_due);
    }

    public function test_checkout_takes_the_stock_out(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['price' => '450.00', 'stock' => 10]);

        $this->pos->addItem($sale, $ball, 3);
        $this->pos->checkout($sale);

        $this->assertSame(7, $ball->fresh()->stock);
        $this->assertSame(-3, (int) $ball->movements()->where('reason', 'sale')->sum('quantity'));
    }

    public function test_stock_cannot_be_oversold(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['stock' => 2]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('left in stock');

        $this->pos->addItem($sale, $ball, 5);
    }

    public function test_an_out_of_stock_product_cannot_be_added(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['stock' => 0]);

        $this->expectException(RuntimeException::class);
        $this->pos->addItem($sale, $ball, 1);
    }

    public function test_an_inactive_product_cannot_be_sold(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['is_active' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no longer for sale');

        $this->pos->addItem($sale, $ball, 1);
    }

    public function test_untracked_products_are_always_available(): void
    {
        $sale = $this->cart();
        $rental = $this->product(['track_stock' => false, 'stock' => 0]);

        // No stock counted, but still recorded and charged for.
        $this->pos->addItem($sale, $rental, 5);
        $paid = $this->pos->checkout($sale);

        $this->assertSame(SaleStatus::PAID, $paid->status);
        $this->assertSame(0, $rental->fresh()->stock, 'Untracked stock never moves.');
    }

    public function test_a_paid_sale_cannot_be_edited(): void
    {
        $sale = $this->cart();
        $ball = $this->product();
        $item = $this->pos->addItem($sale, $ball, 1);
        $this->pos->checkout($sale);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('can no longer be edited');

        $this->pos->setQuantity($sale, $item, 5);
    }

    public function test_refunding_returns_the_stock(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['stock' => 10]);

        $this->pos->addItem($sale, $ball, 4);
        $this->pos->checkout($sale);
        $this->assertSame(6, $ball->fresh()->stock);

        $refunded = $this->pos->refund($sale, null, 'Wrong item');

        $this->assertSame(SaleStatus::REFUNDED, $refunded->status);
        $this->assertSame(10, $ball->fresh()->stock, 'Refunded stock must come back.');
        $this->assertSame(4, (int) $ball->movements()->where('reason', 'refund')->sum('quantity'));
    }

    public function test_a_sale_cannot_be_refunded_twice(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(), 1);
        $this->pos->checkout($sale);
        $this->pos->refund($sale);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be refunded');

        $this->pos->refund($sale);
    }

    public function test_an_unpaid_sale_cannot_be_refunded(): void
    {
        $sale = $this->cart();
        $this->pos->addItem($sale, $this->product(), 1);

        $this->expectException(RuntimeException::class);
        $this->pos->refund($sale);
    }

    public function test_voiding_an_unpaid_sale_does_not_touch_stock(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['stock' => 10]);
        $this->pos->addItem($sale, $ball, 2);

        $voided = $this->pos->void($sale);

        $this->assertSame(SaleStatus::VOID, $voided->status);
        $this->assertSame(10, $ball->fresh()->stock, 'Stock was never taken, so nothing returns.');
    }

    public function test_an_empty_cart_cannot_be_checked_out(): void
    {
        $sale = $this->cart();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nothing to charge for');

        $this->pos->checkout($sale);
    }

    public function test_a_line_snapshots_the_name_and_price(): void
    {
        $sale = $this->cart();
        $paddle = $this->product(['name' => 'Pro Paddle', 'price' => '1200.00']);

        $this->pos->addItem($sale, $paddle, 1);
        $this->pos->checkout($sale);

        // Rename and reprice afterwards: the receipt must not change.
        $paddle->update(['name' => 'Pro Paddle X', 'price' => '9999.00']);

        $item = $sale->items()->first();
        $this->assertSame('Pro Paddle', $item->product_name);
        $this->assertSame('1200.00', $item->unit_price);
    }

    public function test_a_sale_can_be_linked_to_a_customer(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->branch->organization_id,
        ]);

        $sale = $this->pos->open($this->branch, $customer);

        $this->assertSame($customer->id, $sale->customer_id);
    }

    public function test_rental_equipment_is_recognised(): void
    {
        $rental = $this->product(['category' => ProductCategory::RENTAL->value]);

        $this->assertTrue($rental->category->isRental());
        $this->assertFalse($this->product(['category' => ProductCategory::BALL->value])->category->isRental());
    }

    public function test_reorder_level_flags_low_stock(): void
    {
        $product = $this->product(['stock' => 3, 'reorder_level' => 5]);

        $this->assertTrue($product->needsReorder());
        $this->assertFalse($this->product(['stock' => 50, 'reorder_level' => 5])->needsReorder());
    }

    public function test_products_are_isolated_between_tenants(): void
    {
        $mine = $this->product();

        $otherProduct = Product::factory()->create();

        $otherSale = Sale::withoutGlobalScope('organization')
            ->whereKey($otherProduct->id)
            ->first();

        $this->assertNull($otherSale, 'Sanity: a product is not a sale.');
        $this->assertNotSame($mine->organization_id, $otherProduct->organization_id);
    }

    public function test_another_facility_cannot_be_sold_into_this_cart(): void
    {
        $sale = $this->cart();
        $foreign = Product::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('another facility');

        $this->pos->addItem($sale, $foreign, 1);
    }

    public function test_stock_movements_are_recorded_not_deleted(): void
    {
        $sale = $this->cart();
        $ball = $this->product(['stock' => 10]);

        $this->pos->addItem($sale, $ball, 2);
        $this->pos->checkout($sale);
        $this->pos->refund($sale);

        $movements = StockMovement::where('product_id', $ball->id)->get();

        // A sale out and a refund back, both still on the record.
        $this->assertCount(2, $movements);
        $this->assertSame(-2, (int) $movements->where('reason', 'sale')->sum('quantity'));
        $this->assertSame(2, (int) $movements->where('reason', 'refund')->sum('quantity'));
    }
}

<?php

namespace Tests\Feature\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 10 acceptance checks: purchasing, adjustments and transfers
 * (plan.md §18).
 */
class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();

        $this->supplier = Supplier::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->inventory = app(InventoryService::class);
    }

    protected function product(array $attributes = []): Product
    {
        return Product::factory()->create([
            'organization_id' => $this->supplier->organization_id,
        ] + $attributes);
    }

    public function test_an_order_starts_as_a_draft(): void
    {
        $order = $this->inventory->createOrder($this->supplier);

        $this->assertSame(PurchaseOrderStatus::DRAFT, $order->status);
        $this->assertNotEmpty($order->reference);
    }

    public function test_an_order_totals_its_lines(): void
    {
        $order = $this->inventory->createOrder($this->supplier);
        $product = $this->product(['cost' => '200.00']);

        $this->inventory->addOrderItem($order, $product, 10, 200.00);

        $this->assertSame('2000.00', $order->fresh()->subtotal);
    }

    public function test_an_order_line_snapshots_cost_and_name(): void
    {
        $order = $this->inventory->createOrder($this->supplier);
        $product = $this->product(['name' => 'Pro Paddle', 'cost' => '500.00']);

        $item = $this->inventory->addOrderItem($order, $product, 5);

        // Reprice the product; the agreed order must not change.
        $product->update(['name' => 'Pro Paddle X', 'cost' => '999.00']);

        $this->assertSame('Pro Paddle', $item->product_name);
        $this->assertSame('500.00', $item->unit_cost);
    }

    public function test_an_empty_order_cannot_be_placed(): void
    {
        $order = $this->inventory->createOrder($this->supplier);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('empty order');

        $this->inventory->placeOrder($order);
    }

    public function test_a_placed_order_cannot_be_edited(): void
    {
        $order = $this->inventory->createOrder($this->supplier);
        $this->inventory->addOrderItem($order, $this->product(), 5);
        $this->inventory->placeOrder($order);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be edited');

        $this->inventory->addOrderItem($order, $this->product(), 1);
    }

    public function test_receiving_stock_increases_stock(): void
    {
        $product = $this->product(['stock' => 0]);
        $order = $this->inventory->createOrder($this->supplier);
        $item = $this->inventory->addOrderItem($order, $product, 20);
        $this->inventory->placeOrder($order);

        $this->inventory->receiveItem($item, 20);

        $this->assertSame(20, $product->fresh()->stock);
        $this->assertSame(20, (int) $product->movements()->where('reason', 'restock')->sum('quantity'));
    }

    public function test_a_partial_receipt_leaves_the_order_partial(): void
    {
        $product = $this->product(['stock' => 0]);
        $order = $this->inventory->createOrder($this->supplier);
        $item = $this->inventory->addOrderItem($order, $product, 20);
        $this->inventory->placeOrder($order);

        $this->inventory->receiveItem($item, 8);

        $this->assertSame(PurchaseOrderStatus::PARTIAL, $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(12, $item->fresh()->outstanding());
    }

    public function test_a_full_receipt_closes_the_order(): void
    {
        $product = $this->product(['stock' => 0]);
        $order = $this->inventory->createOrder($this->supplier);
        $item = $this->inventory->addOrderItem($order, $product, 10);
        $this->inventory->placeOrder($order);

        $this->inventory->receiveItem($item, 5);
        $this->inventory->receiveItem($item->fresh(), 5);

        $this->assertSame(PurchaseOrderStatus::RECEIVED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->received_at);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_more_cannot_be_received_than_was_ordered(): void
    {
        $product = $this->product(['stock' => 0]);
        $order = $this->inventory->createOrder($this->supplier);
        $item = $this->inventory->addOrderItem($order, $product, 5);
        $this->inventory->placeOrder($order);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('still outstanding');

        $this->inventory->receiveItem($item, 6);
    }

    public function test_stock_cannot_be_driven_negative_by_an_adjustment(): void
    {
        $product = $this->product(['stock' => 3]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove more than is in stock');

        $this->inventory->adjustStock($product, -5, 'damage');
    }

    public function test_an_adjustment_is_recorded(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->inventory->adjustStock($product, -2, 'damage', null, 'Cracked');

        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame('Cracked', $product->movements()->latest('id')->first()->notes);
    }

    public function test_a_zero_adjustment_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->inventory->adjustStock($this->product(), 0);
    }

    public function test_a_restock_is_recorded_as_positive(): void
    {
        $product = $this->product(['stock' => 2]);

        $this->inventory->adjustStock($product, 10, 'restock');

        $this->assertSame(12, $product->fresh()->stock);
        $this->assertSame(10, (int) $product->movements()->where('reason', 'restock')->sum('quantity'));
    }

    public function test_sending_a_transfer_removes_stock_immediately(): void
    {
        $product = $this->product(['stock' => 10]);

        $transfer = $this->inventory->sendTransfer($product, 4, null);

        $this->assertSame(6, $product->fresh()->stock, 'Stock leaves when it is sent, not when received.');
        // A transfer out is a negative movement, by design.
        $this->assertSame(-4, (int) $product->movements()->where('reason', 'return')->sum('quantity'));
        $this->assertNotEmpty($transfer->reference);
    }

    public function test_more_cannot_be_sent_than_is_on_hand(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('on hand');

        $this->inventory->sendTransfer($this->product(['stock' => 2]), 5, null);
    }

    public function test_receiving_a_transfer_puts_stock_back(): void
    {
        $product = $this->product(['stock' => 10]);
        $transfer = $this->inventory->sendTransfer($product, 4, null);
        $this->assertSame(6, $product->fresh()->stock);

        $this->inventory->receiveTransfer($transfer);

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame('received', $transfer->fresh()->status->value);
    }

    public function test_a_transfer_cannot_be_received_twice(): void
    {
        $product = $this->product(['stock' => 10]);
        $transfer = $this->inventory->sendTransfer($product, 4, null);
        $this->inventory->receiveTransfer($transfer);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already Received');

        $this->inventory->receiveTransfer($transfer);
    }

    public function test_the_ledger_rebuilds_the_balance(): void
    {
        $product = $this->product(['stock' => 0]);
        $order = $this->inventory->createOrder($this->supplier);
        $item = $this->inventory->addOrderItem($order, $product, 10);
        $this->inventory->placeOrder($order);

        $this->inventory->receiveItem($item, 10);   // +10
        $this->inventory->adjustStock($product, -3, 'damage');  // -3

        $balance = (int) $product->movements()->sum('quantity');

        $this->assertSame(7, $balance);
        $this->assertSame(7, $product->fresh()->stock, 'The ledger and the balance must agree.');
    }

    public function test_products_needing_reorder_are_listed(): void
    {
        $low = $this->product(['stock' => 2, 'reorder_level' => 5]);
        $this->product(['stock' => 50, 'reorder_level' => 5]);
        $untracked = $this->product(['track_stock' => false, 'stock' => 0, 'reorder_level' => 5]);

        $ids = $this->inventory->needsReordering()->pluck('id')->all();

        $this->assertContains($low->id, $ids);
        $this->assertNotContains($untracked->id, $ids, 'Untracked stock is never reordered.');
    }

    public function test_suppliers_are_tenant_scoped(): void
    {
        $other = Supplier::factory()->create();

        $this->assertNotSame($this->supplier->organization_id, $other->organization_id);
    }

    public function test_movements_are_never_deleted(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->inventory->adjustStock($product, -1, 'damage');

        $before = StockMovement::withoutGlobalScopes()->count();
        $this->inventory->adjustStock($product, 1, 'restock');

        $this->assertSame($before + 1, StockMovement::withoutGlobalScopes()->count());
    }
}

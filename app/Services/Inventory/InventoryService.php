<?php

namespace App\Services\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockTransferStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Purchasing, stock adjustments and transfers (plan.md §18).
 *
 * Every stock change locks the product row and writes an immutable movement,
 * so the balance can always be explained and rebuilt from the ledger.
 */
class InventoryService
{
    /**
     * Start a purchase order for a supplier.
     */
    public function createOrder(Supplier $supplier, ?int $branchId = null, ?User $createdBy = null): PurchaseOrder
    {
        return PurchaseOrder::create([
            'organization_id' => $supplier->organization_id,
            'branch_id' => $branchId,
            'supplier_id' => $supplier->id,
            'created_by' => $createdBy?->id,
            'status' => PurchaseOrderStatus::DRAFT,
        ]);
    }

    /**
     * Add a line to a draft order.
     */
    public function addOrderItem(PurchaseOrder $order, Product $product, int $quantity, ?float $unitCost = null): PurchaseOrderItem
    {
        $this->guardDraft($order);

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }

        $item = $order->items()->create([
            'product_id' => $product->id,
            // Snapshots, so a later cost change cannot rewrite the order.
            'product_name' => $product->name,
            'unit_cost' => $unitCost ?? $product->cost,
            'quantity_ordered' => $quantity,
        ]);

        $order->recalculate();

        return $item;
    }

    /**
     * Place the order with the supplier.
     */
    public function placeOrder(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== PurchaseOrderStatus::DRAFT) {
            throw new RuntimeException("A {$order->status->label()} order cannot be placed.");
        }

        if ($order->items()->count() === 0) {
            throw new RuntimeException('An empty order cannot be placed.');
        }

        $order->update([
            'status' => PurchaseOrderStatus::ORDERED,
            'ordered_at' => now(),
        ]);

        return $order;
    }

    /**
     * Book stock in against an order line.
     *
     * @throws RuntimeException when more is received than was ordered
     */
    public function receiveItem(PurchaseOrderItem $item, int $quantity, ?User $receiver = null): PurchaseOrderItem
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }

        return DB::transaction(function () use ($item, $quantity, $receiver): PurchaseOrderItem {
            $lockedItem = PurchaseOrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $order = $lockedItem->order;

            $this->guardReceiving($order);

            $outstanding = $lockedItem->outstanding();

            if ($quantity > $outstanding) {
                throw new RuntimeException(
                    "Only {$outstanding} of {$lockedItem->product_name} are still outstanding."
                );
            }

            $lockedItem->quantity_received = (int) $lockedItem->quantity_received + $quantity;
            $lockedItem->save();

            $product = Product::query()->whereKey($lockedItem->product_id)->lockForUpdate()->firstOrFail();
            $product->increment('stock', $quantity);

            StockMovement::create([
                'organization_id' => $product->organization_id,
                'product_id' => $product->id,
                'user_id' => $receiver?->id,
                'quantity' => $quantity,
                'reason' => 'restock',
                'notes' => "Received against {$order->reference}",
            ]);

            $this->refreshOrderStatus($order, $receiver);

            return $lockedItem;
        }, 3);
    }

    /**
     * Adjust stock by hand, e.g. damage or a recount.
     */
    public function adjustStock(Product $product, int $delta, string $reason = 'adjustment', ?User $actor = null, ?string $notes = null): StockMovement
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('An adjustment of zero does nothing.');
        }

        return DB::transaction(function () use ($product, $delta, $reason, $actor, $notes): StockMovement {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($locked->stock + $delta < 0) {
                throw new RuntimeException(
                    "Cannot remove more than is in stock. {$locked->stock} on hand."
                );
            }

            $locked->stock = $locked->stock + $delta;
            $locked->save();

            return StockMovement::create([
                'organization_id' => $locked->organization_id,
                'product_id' => $locked->id,
                'user_id' => $actor?->id,
                'quantity' => $delta,
                'reason' => $reason,
                'notes' => $notes,
            ]);
        }, 3);
    }

    /**
     * Send stock to another branch. Leaves the sending branch immediately.
     */
    public function sendTransfer(Product $product, int $quantity, ?int $toBranchId, ?User $actor = null): StockTransfer
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }

        return DB::transaction(function () use ($product, $quantity, $toBranchId, $actor): StockTransfer {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($locked->stock < $quantity) {
                throw new RuntimeException("Only {$locked->stock} of {$locked->name} on hand.");
            }

            $locked->decrement('stock', $quantity);

            $transfer = StockTransfer::create([
                'organization_id' => $locked->organization_id,
                'product_id' => $locked->id,
                'from_branch_id' => $locked->branch_id,
                'to_branch_id' => $toBranchId,
                'user_id' => $actor?->id,
                'quantity' => $quantity,
                'status' => 'in_transit',
            ]);

            StockMovement::create([
                'organization_id' => $locked->organization_id,
                'product_id' => $locked->id,
                'user_id' => $actor?->id,
                'quantity' => -$quantity,
                'reason' => 'return',
                'notes' => "Transfer {$transfer->reference} sent",
            ]);

            return $transfer;
        }, 3);
    }

    /**
     * Receive a transfer, putting the stock back into the pool.
     */
    public function receiveTransfer(StockTransfer $transfer, ?User $actor = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $actor): StockTransfer {
            $locked = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isSettled()) {
                throw new RuntimeException("This transfer is already {$locked->status->label()}.");
            }

            $product = Product::query()->whereKey($locked->product_id)->lockForUpdate()->firstOrFail();
            $product->increment('stock', (int) $locked->quantity);

            $locked->update([
                'status' => StockTransferStatus::RECEIVED,
                'received_at' => now(),
            ]);

            StockMovement::create([
                'organization_id' => $product->organization_id,
                'product_id' => $product->id,
                'user_id' => $actor?->id,
                'quantity' => (int) $locked->quantity,
                'reason' => 'restock',
                'notes' => "Transfer {$locked->reference} received",
            ]);

            return $locked;
        }, 3);
    }

    /**
     * Products at or below their reorder level.
     *
     * @return Collection<int, Product>
     */
    public function needsReordering(): Collection
    {
        return Product::query()
            ->where('track_stock', true)
            ->whereColumn('stock', '<=', 'reorder_level')
            ->orderBy('stock')
            ->get();
    }

    /**
     * Roll the order up to partial or fully received.
     */
    protected function refreshOrderStatus(PurchaseOrder $order, ?User $receiver): void
    {
        $items = $order->items()->get();

        $allReceived = $items->every(fn (PurchaseOrderItem $item): bool => $item->isFullyReceived());
        $anyReceived = $items->contains(fn (PurchaseOrderItem $item): bool => (int) $item->quantity_received > 0);

        $order->status = match (true) {
            $allReceived => PurchaseOrderStatus::RECEIVED,
            $anyReceived => PurchaseOrderStatus::PARTIAL,
            default => PurchaseOrderStatus::ORDERED,
        };

        if ($order->status === PurchaseOrderStatus::RECEIVED) {
            $order->received_at = now();
            $order->received_by = $receiver?->id;
        }

        $order->save();
    }

    protected function guardDraft(PurchaseOrder $order): void
    {
        if ($order->status !== PurchaseOrderStatus::DRAFT) {
            throw new RuntimeException("A {$order->status->label()} order cannot be edited.");
        }
    }

    protected function guardReceiving(PurchaseOrder $order): void
    {
        if (! $order->status->acceptsReceiving()) {
            throw new RuntimeException("A {$order->status->label()} order cannot receive stock.");
        }
    }
}

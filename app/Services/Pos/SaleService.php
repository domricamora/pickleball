<?php

namespace App\Services\Pos;

use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * The point-of-sale cart (plan.md §17).
 *
 * Two invariants drive the design:
 *  1. the money columns are always recomputed from the line items, so a total
 *     can never drift from the cart it claims to describe;
 *  2. checking out and refunding both lock the product rows they touch, so two
 *     tills cannot sell the last item.
 */
class SaleService
{
    /**
     * Open a new cart.
     */
    public function open(Branch $branch, ?Customer $customer = null, ?User $cashier = null, ?int $bookingId = null): Sale
    {
        return Sale::create([
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer?->id,
            'user_id' => $cashier?->id,
            'booking_id' => $bookingId,
            'status' => SaleStatus::OPEN,
        ]);
    }

    /**
     * Add a product to the cart, merging into the existing line if present.
     *
     * @throws RuntimeException when the product cannot be sold
     */
    public function addItem(Sale $sale, Product $product, int $quantity = 1): SaleItem
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }

        if ($product->organization_id !== $sale->organization_id) {
            throw new RuntimeException('That product belongs to another facility.');
        }

        if (! $product->is_active) {
            throw new RuntimeException("{$product->name} is no longer for sale.");
        }

        if (! $product->track_stock) {
            return $this->appendLine($sale, $product, $quantity);
        }

        $stock = $this->availableStock($product);

        $existing = (int) ($sale->items()
            ->where('product_id', $product->id)
            ->value('quantity') ?? 0);

        if ($existing + $quantity > $stock) {
            throw new RuntimeException(
                "Only {$stock} of {$product->name} left in stock."
            );
        }

        return $this->appendLine($sale, $product, $quantity);
    }

    /**
     * Set the quantity of an existing line. Zero removes it.
     */
    public function setQuantity(Sale $sale, SaleItem $item, int $quantity): void
    {
        $this->guardOpen($sale);

        if ($quantity < 1) {
            $item->delete();

            return;
        }

        $product = $item->product;

        if ($product !== null && $product->track_stock) {
            $stock = $this->availableStock($product);
            $otherLines = (int) ($sale->items()
                ->where('product_id', $product->id)
                ->whereKeyNot($item->id)
                ->value('quantity') ?? 0);

            if ($otherLines + $quantity > $stock) {
                throw new RuntimeException("Only {$stock} of {$product->name} left in stock.");
            }
        }

        $item->update([
            'quantity' => $quantity,
            'line_total' => Money::toFloat($quantity * (float) $item->unit_price),
        ]);
    }

    /**
     * Apply a flat discount to the whole cart.
     */
    public function applyDiscount(Sale $sale, float $discount): Sale
    {
        $this->guardOpen($sale);

        if ($discount < 0) {
            throw new InvalidArgumentException('A discount cannot be negative.');
        }

        if ($discount > (float) $sale->subtotal) {
            throw new RuntimeException(
                'A discount cannot be more than the subtotal. '
                .Money::format((float) $sale->subtotal).' is the subtotal.'
            );
        }

        $sale->discount = Money::toFloat($discount);

        $this->recalculate($sale);

        return $sale;
    }

    /**
     * Take payment and close the sale, decrementing stock as we go.
     *
     * @param  float  $tendered  Cash handed over; change is worked out here.
     */
    public function checkout(Sale $sale, float $tendered = 0.0, ?User $cashier = null): Sale
    {
        if ($tendered < 0) {
            throw new InvalidArgumentException('Amount tendered cannot be negative.');
        }

        return DB::transaction(function () use ($sale, $tendered, $cashier): Sale {
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            $this->guardOpen($locked);
            $this->recalculate($locked);

            if ((float) $locked->total <= 0) {
                throw new RuntimeException('There is nothing to charge for.');
            }

            $this->decrementStock($locked);

            $locked->status = SaleStatus::PAID;
            $locked->paid_at = now();
            // A null cashier means "keep whoever opened the sale".
            if ($cashier !== null) {
                $locked->processed_by = $cashier->id;
            }

            $locked->amount_tendered = Money::toFloat($tendered);
            $locked->change_due = Money::toFloat(max(0, $tendered - (float) $locked->total));
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * Refund a paid sale and return the stock.
     */
    public function refund(Sale $sale, ?User $actor = null, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($sale, $actor, $reason): Sale {
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isSettled()) {
                throw new RuntimeException("A {$locked->status->label()} sale cannot be refunded.");
            }

            $this->restoreStock($locked, $actor, $reason);

            $locked->status = SaleStatus::REFUNDED;
            $locked->refunded_at = now();
            $locked->notes = $reason ?? $locked->notes;
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * Abandon a cart that was never paid for. Stock was never taken, so
     * nothing is returned.
     */
    public function void(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale): Sale {
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            $this->guardOpen($locked);

            $locked->status = SaleStatus::VOID;
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * Recompute subtotal, tax and total from the line items.
     */
    public function recalculate(Sale $sale): Sale
    {
        $subtotal = 0.0;
        $tax = 0.0;

        foreach ($sale->items()->get() as $item) {
            $line = (float) $item->unit_price * (int) $item->quantity;
            $subtotal += $line;
            $tax += $line * ((float) $item->tax_rate / 100);
        }

        $subtotal = Money::toFloat($subtotal);
        $tax = Money::toFloat($tax);
        $discount = Money::toFloat($sale->discount);

        // Tax applies to what the customer actually pays, so a discount
        // reduces it proportionally rather than being added on top.
        $tax = Money::toFloat($subtotal > 0 ? $tax * (($subtotal - $discount) / $subtotal) : 0);
        $total = Money::toFloat($subtotal - $discount + $tax);

        $sale->subtotal = $subtotal;
        $sale->tax = $tax;
        $sale->total = $total;
        $sale->save();

        return $sale;
    }

    /**
     * Create or merge a line.
     */
    protected function appendLine(Sale $sale, Product $product, int $quantity): SaleItem
    {
        $this->guardOpen($sale);

        $existing = $sale->items()->where('product_id', $product->id)->first();

        if ($existing !== null) {
            $newQuantity = (int) $existing->quantity + $quantity;

            $existing->update([
                'quantity' => $newQuantity,
                'line_total' => Money::toFloat($newQuantity * (float) $existing->unit_price),
            ]);

            $this->recalculate($sale);

            return $existing;
        }

        $item = $sale->items()->create([
            'product_id' => $product->id,
            // Snapshots: history survives a rename or a price change.
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'line_total' => Money::toFloat($quantity * (float) $product->price),
            'tax_rate' => $product->tax_rate,
        ]);

        $this->recalculate($sale);

        return $item;
    }

    /**
     * Take the sold quantity out of stock, recording each movement.
     */
    protected function decrementStock(Sale $sale): void
    {
        foreach ($sale->items()->with('product')->get() as $item) {
            $product = $item->product;

            if ($product === null || ! $product->track_stock) {
                continue;
            }

            // Lock the product so two tills cannot both sell the last unit.
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $quantity = (int) $item->quantity;

            if ($lockedProduct->stock < $quantity) {
                throw new RuntimeException(
                    "Only {$lockedProduct->stock} of {$lockedProduct->name} left in stock."
                );
            }

            $lockedProduct->decrement('stock', $quantity);

            StockMovement::create([
                'organization_id' => $lockedProduct->organization_id,
                'product_id' => $lockedProduct->id,
                'sale_id' => $sale->id,
                'quantity' => -$quantity,
                'reason' => 'sale',
            ]);
        }
    }

    /**
     * Put returned stock back, recording each movement.
     */
    protected function restoreStock(Sale $sale, ?User $actor, ?string $reason): void
    {
        foreach ($sale->items()->with('product')->get() as $item) {
            $product = $item->product;

            if ($product === null || ! $product->track_stock) {
                continue;
            }

            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $lockedProduct->increment('stock', (int) $item->quantity);

            StockMovement::create([
                'organization_id' => $lockedProduct->organization_id,
                'product_id' => $lockedProduct->id,
                'sale_id' => $sale->id,
                'user_id' => $actor?->id,
                'quantity' => (int) $item->quantity,
                'reason' => 'refund',
                'notes' => $reason,
            ]);
        }
    }

    /**
     * Stock on hand. Reservations are applied at checkout under a row lock,
     * so the cart check here is advisory and the checkout check is the one
     * that actually guarantees availability.
     */
    protected function availableStock(Product $product): int
    {
        return $product->track_stock ? $product->stock : PHP_INT_MAX;
    }

    /**
     * Refuse edits to a sale that is no longer open.
     *
     * The status is re-read from the database rather than trusted from the
     * in-memory model: a caller holding a sale it loaded before checkout
     * would otherwise still see it as open and could edit a paid receipt.
     */
    protected function guardOpen(Sale $sale): void
    {
        $status = Sale::query()->whereKey($sale->id)->value('status');

        if ($status === null) {
            throw new RuntimeException('This sale no longer exists.');
        }

        // The model casts status, so this may already be an enum.
        $enum = $status instanceof SaleStatus ? $status : SaleStatus::from($status);

        if (! $enum->isOpen()) {
            throw new RuntimeException("This sale is {$enum->label()} and can no longer be edited.");
        }
    }
}

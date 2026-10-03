<?php

namespace Database\Seeders\Concerns;

use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * POS sales and running costs.
 *
 * Product revenue is one of the three sources the reporting service sums, and
 * expenses are what turn that revenue into a profit figure rather than a bare
 * takings total (plan.md §17, §20).
 */
trait SeedsTrading
{
    /**
     * @param  array<int, int>  $customerIds
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Staff>  $staff
     */
    private function seedSales(
        int $orgId,
        int $branchId,
        Collection $products,
        array $customerIds,
        Collection $staff,
    ): void {
        if ($products->isEmpty()) {
            return;
        }

        // Weighted towards the hours a club is actually busy.
        $hours = [10, 11, 14, 16, 17, 18, 19, 20, 21];

        // Roughly three POS sales a day for a three-court club: drinks, grips and
        // the occasional paddle. Enough to populate the sales list without
        // inflating product revenue past the booking takings.
        for ($i = 0; $i < 45; $i++) {
            $soldAt = now()
                ->subDays(random_int(0, 89))
                ->setTime(fake()->randomElement($hours), fake()->numberBetween(0, 59));

            // One or two lines: a drink and a grip, or the occasional paddle.
            $lines = fake()->numberBetween(1, 2);
            $picked = array_rand($products->all(), min($lines, $products->count()));

            $subtotal = 0.0;
            $rows = [];

            foreach ((array) $picked as $productIndex) {
                $product = $products[$productIndex];
                $quantity = fake()->numberBetween(1, 3);
                $lineTotal = round((float) $product->price * $quantity, 2);
                $subtotal += $lineTotal;

                $rows[] = ['product' => $product, 'quantity' => $quantity, 'line_total' => $lineTotal];
            }

            // 12% VAT, the standard Philippine business rate.
            $tax = round($subtotal * 0.12, 2);
            $total = round($subtotal + $tax, 2);
            $tendered = fake()->boolean(60) ? $total : (float) (ceil($total / 100) * 100);

            $sale = Sale::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'customer_id' => fake()->randomElement($customerIds),
                'reference' => 'SALE-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'status' => SaleStatus::PAID->value,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'discount' => '0.00',
                'tax' => number_format($tax, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'amount_tendered' => number_format($tendered, 2, '.', ''),
                'change_due' => number_format(round($tendered - $total, 2), 2, '.', ''),
                'currency' => 'PHP',
                'paid_at' => $soldAt,
                'created_at' => $soldAt,
                'updated_at' => $soldAt,
            ]);

            foreach ($rows as $row) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'product_name' => $row['product']->name,
                    'sku' => $row['product']->sku,
                    'unit_price' => $row['product']->price,
                    'quantity' => $row['quantity'],
                    'line_total' => number_format($row['line_total'], 2, '.', ''),
                    'tax_rate' => 12.00,
                ]);

                StockMovement::create([
                    'organization_id' => $orgId,
                    'product_id' => $row['product']->id,
                    'sale_id' => $sale->id,
                    'quantity' => -$row['quantity'],
                    'reason' => 'sale',
                    'notes' => 'Sold on '.$sale->reference.'.',
                    'created_at' => $soldAt,
                ]);
            }
        }
    }

    /**
     * Recorded by a user account, because expenses.recorded_by is a foreign key
     * to `users` -- not to staff (see the expenses migration).
     *
     * @param  Collection<int, User>  $recorders
     */
    private function seedExpenses(
        int $orgId,
        int $branchId,
        Collection $recorders,
    ): void {
        /*
         * Costs are sized to a club of this size rather than copied from a
         * large venue: three courts cannot support a six-figure lease, and a
         * dashboard showing a permanent six-figure loss reads as a broken
         * seeder rather than as a business. These figures are plausible for a
         * three-court Makati club.
         */
        $categories = [
            ['Utilities', 'Electricity - Meralco', 6500.00, 9500.00],
            ['Utilities', 'Water - Maynilad', 1800.00, 2800.00],
            ['Maintenance', 'Court resurfacing supplies', 2500.00, 6000.00],
            ['Maintenance', 'Net and pole replacement', 1200.00, 3000.00],
            ['Marketing', 'Social media ads', 3000.00, 7000.00],
            ['Marketing', 'Flyers and signage', 800.00, 2200.00],
            ['Rent', 'Facility lease - monthly', 35000.00, 35000.00],
            ['Admin', 'Accounting and bookkeeping', 4000.00, 4000.00],
            // Payroll is the dominant real cost, and including it is what makes
            // the profit figure believable rather than flattering.
            ['Payroll', 'Staff salaries - monthly', 68000.00, 78000.00],
        ];

        // Rent, utilities and payroll post once a month; the rest are ad hoc. A
        // club that pays rent fortnightly would show a false run rate.
        $monthly = ['Rent', 'Utilities', 'Payroll', 'Admin'];

        for ($month = 0; $month < 3; $month++) {
            foreach ($categories as $index => $expense) {
                $isMonthly = in_array($expense[0], $monthly, true);

                // Ad hoc lines do not post every month.
                if (! $isMonthly && fake()->boolean(55)) {
                    continue;
                }

                $incurredOn = now()
                    ->subMonths($month)
                    ->day(random_int(1, 5))
                    ->startOfDay();

                Expense::create([
                    'organization_id' => $orgId,
                    'branch_id' => $branchId,
                    'recorded_by' => fake()->randomElement($recorders)->id,
                    'reference' => 'EXP-'.str_pad((string) (($month * 10) + $index + 1), 5, '0', STR_PAD_LEFT),
                    'category' => $expense[0],
                    'description' => $expense[1],
                    'amount' => number_format(fake()->randomFloat(2, $expense[2], $expense[3]), 2, '.', ''),
                    'currency' => 'PHP',
                    'incurred_on' => $incurredOn->toDateString(),
                    'created_at' => $incurredOn,
                    'updated_at' => $incurredOn,
                ]);
            }
        }
    }
}

<?php

namespace App\Services\Reports;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Operational finance (plan.md §20).
 *
 * Every figure is derived from the tables that actually hold the money —
 * payments, sales, memberships, bookings. Nothing is copied into a report
 * table, because a copied number can silently drift from reality.
 */
class ReportingService
{
    /**
     * Money actually collected, net of refunds.
     */
    public function netRevenue(Carbon $from, Carbon $to, ?int $branchId = null): float
    {
        $bookingRevenue = $this->bookingRevenue($from, $to, $branchId);
        $productRevenue = $this->productRevenue($from, $to, $branchId);
        $membershipRevenue = $this->membershipRevenue($from, $to);

        return round($bookingRevenue + $productRevenue + $membershipRevenue, 2);
    }

    /**
     * Revenue split by where it came from.
     *
     * @return array<string, float>
     */
    public function revenueBySource(Carbon $from, Carbon $to): array
    {
        return [
            'bookings' => $this->bookingRevenue($from, $to),
            'products' => $this->productRevenue($from, $to),
            'memberships' => $this->membershipRevenue($from, $to),
        ];
    }

    /**
     * Court booking payments, net of refunds.
     */
    public function bookingRevenue(Carbon $from, Carbon $to, ?int $branchId = null): float
    {
        $query = Payment::query()
            ->whereNotNull('booking_id')
            ->whereBetween('paid_at', [$from, $to]);

        if ($branchId !== null) {
            $query->whereHas('booking', fn ($q) => $q->where('branch_id', $branchId));
        }

        return $this->netOfQuery($query);
    }

    /**
     * Product sales, net of refunded sales.
     */
    public function productRevenue(Carbon $from, Carbon $to, ?int $branchId = null): float
    {
        $query = Sale::query()
            ->where('status', SaleStatus::PAID->value)
            ->whereBetween('paid_at', [$from, $to]);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return round((float) $query->sum('total'), 2);
    }

    /**
     * Membership fees collected.
     */
    public function membershipRevenue(Carbon $from, Carbon $to): float
    {
        $revenue = (float) MembershipSubscription::query()
            ->join('membership_plans', 'membership_plans.id', '=', 'membership_subscriptions.membership_plan_id')
            // Both tables have created_at, so the subscription's must be
            // qualified or the query is ambiguous.
            ->where('membership_subscriptions.created_at', '>=', $from)
            ->where('membership_subscriptions.created_at', '<=', $to)
            ->sum('membership_plans.price');

        return round($revenue, 2);
    }

    /**
     * Total expenses in the window.
     */
    public function totalExpenses(Carbon $from, Carbon $to, ?int $branchId = null): float
    {
        $query = Expense::query()->whereBetween('incurred_on', [$from->toDateString(), $to->toDateString()]);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return round((float) $query->sum('amount'), 2);
    }

    /**
     * Expenses grouped by category, largest first.
     *
     * @return array<string, float>
     */
    public function expensesByCategory(Carbon $from, Carbon $to): array
    {
        $rows = Expense::query()
            ->whereBetween('incurred_on', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('category, SUM(amount) AS total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $out = [];

        foreach ($rows as $row) {
            // 	otal is a selectRaw alias, invisible to static analysis.
            $out[$row->category] = round((float) $row->getAttribute('total'), 2);
        }

        return $out;
    }

    /**
     * Net revenue after expenses.
     */
    public function profit(Carbon $from, Carbon $to, ?int $branchId = null): float
    {
        return round(
            $this->netRevenue($from, $to, $branchId) - $this->totalExpenses($from, $to, $branchId),
            2,
        );
    }

    /**
     * Revenue per branch, largest first.
     *
     * @return array<string, float>
     */
    public function revenueByBranch(Carbon $from, Carbon $to): array
    {
        $branches = Branch::query()->orderBy('name')->get();
        $out = [];

        foreach ($branches as $branch) {
            $out[$branch->name] = $this->netRevenue($from, $to, $branch->id);
        }

        arsort($out);

        return $out;
    }

    /**
     * Court utilisation: hours booked against hours available.
     *
     * Only counting bookings that actually happened, so a cancellation does
     * not make a court look busy.
     *
     * @return array<string, float>
     */
    public function courtUtilisation(Carbon $from, Carbon $to): array
    {
        $days = max(1, (int) $from->diffInDays($to) + 1);

        $bookings = Booking::query()
            ->whereIn('status', [
                BookingStatus::COMPLETED->value,
                BookingStatus::CHECKED_IN->value,
            ])
            ->whereBetween('starts_at', [$from, $to])
            ->get(['court_id', 'duration_minutes']);

        $byCourt = [];

        foreach ($bookings as $booking) {
            $byCourt[$booking->court_id] = ($byCourt[$booking->court_id] ?? 0)
                + (int) $booking->duration_minutes;
        }

        $courts = Court::query()->get(['id', 'name']);
        $out = [];

        foreach ($courts as $court) {
            // 14 bookable hours a day is the default open span.
            $available = $days * 14 * 60;
            $used = $byCourt[$court->id] ?? 0;

            $out[$court->name] = round(($used / $available) * 100, 1);
        }

        arsort($out);

        return $out;
    }

    /**
     * Revenue by how payments were made.
     *
     * @return array<string, float>
     */
    public function revenueByPaymentMethod(Carbon $from, Carbon $to): array
    {
        $rows = Payment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->whereIn('status', [
                PaymentStatus::PAID->value,
                PaymentStatus::PARTIALLY_REFUNDED->value,
                PaymentStatus::REFUNDED->value,
            ])
            ->selectRaw('method, SUM(amount - refunded_amount) AS total')
            ->groupBy('method')
            ->get();

        $out = [];

        foreach ($rows as $row) {
            // method is cast to an enum, which cannot be used as an array key.
            $out[$row->method->value] = round((float) $row->getAttribute('total'), 2);
        }

        arsort($out);

        return $out;
    }

    /**
     * Bookings with no settled payment still outstanding.
     */
    public function outstandingAmounts(Carbon $from, Carbon $to): float
    {
        $paid = Payment::query()
            ->whereIn('status', [
                PaymentStatus::PAID->value,
                PaymentStatus::PARTIALLY_REFUNDED->value,
                PaymentStatus::REFUNDED->value,
            ])
            ->pluck('booking_id')
            ->filter()
            ->all();

        $owed = (float) Booking::query()
            ->whereNotNull('customer_id')
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::COMPLETED->value,
                BookingStatus::CHECKED_IN->value,
            ])
            ->whereBetween('starts_at', [$from, $to])
            ->whereNotIn('id', $paid)
            ->sum('amount');

        return round($owed, 2);
    }

    /**
     * The headline dashboard figures (plan.md §20).
     *
     * @return array<string, float|int>
     */
    public function kpis(Carbon $from, Carbon $to): array
    {
        $bookings = Booking::query()
            ->whereIn('status', [
                BookingStatus::COMPLETED->value,
                BookingStatus::CHECKED_IN->value,
            ])
            ->whereBetween('starts_at', [$from, $to]);

        $count = (clone $bookings)->count();
        $value = $this->bookingRevenue($from, $to);

        $activeMembers = MembershipSubscription::query()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereDate('ends_on', '>=', now())
            ->count();

        $newCustomers = Customer::query()
            ->where('created_at', '>=', $from)
            ->count();

        return [
            'revenue' => $value,
            'bookings' => $count,
            'average_booking_value' => $count > 0 ? round($value / $count, 2) : 0.0,
            'active_members' => $activeMembers,
            'new_customers' => $newCustomers,
            'expenses' => $this->totalExpenses($from, $to),
            'profit' => $this->profit($from, $to),
        ];
    }

    /**
     * A formatted peso summary, for a report header.
     *
     * @return array<string, string>
     */
    public function formattedSummary(Carbon $from, Carbon $to): array
    {
        return [
            'revenue' => Money::format($this->netRevenue($from, $to)),
            'expenses' => Money::format($this->totalExpenses($from, $to)),
            'profit' => Money::format($this->profit($from, $to)),
        ];
    }

    /**
     * Sum a payment query, netting off what was refunded.
     */
    protected function netOfQuery($query): float
    {
        return round((float) $query
            ->whereIn('status', [
                PaymentStatus::PAID->value,
                PaymentStatus::PARTIALLY_REFUNDED->value,
                PaymentStatus::REFUNDED->value,
            ])
            ->sum(DB::raw('amount - refunded_amount')), 2);
    }
}

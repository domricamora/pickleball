<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Services\Reports\ReportingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The operator's landing screen (plan.md §20).
 *
 * Every figure comes from ReportingService, which reads the tables that hold
 * the money rather than a denormalised copy, so the dashboard cannot drift
 * from the bookings and payments behind it.
 */
class DashboardController extends Controller
{
    public function __construct(protected ReportingService $reports) {}

    /**
     * The allowed reporting windows, in days.
     *
     * @return array<int, int>
     */
    protected function windows(): array
    {
        return [7, 30, 90];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $validated = $request->validate([
            'range' => ['nullable', 'integer', Rule::in($this->windows())],
        ]);

        $days = (int) ($validated['range'] ?? 30);

        // The window ends at the close of today so bookings made a minute ago
        // are counted, rather than being excluded by a start-of-day bound.
        $to = now()->endOfDay();
        $from = now()->subDays($days - 1)->startOfDay();

        $kpis = $this->reports->kpis($from, $to);

        return Inertia::render('admin/Dashboard', [
            'range' => $days,
            'ranges' => $this->windows(),
            'window' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $from->format('M j').' – '.$to->format('M j, Y'),
            ],
            'kpis' => [
                'revenue' => Money::format($kpis['revenue']),
                'expenses' => Money::format($kpis['expenses']),
                'profit' => Money::format($kpis['profit']),
                'bookings' => $kpis['bookings'],
                'average_booking_value' => Money::format($kpis['average_booking_value']),
                'active_members' => $kpis['active_members'],
                'new_customers' => $kpis['new_customers'],
                // Signed so the UI can colour a loss without parsing the peso
                // string, which would be fragile.
                'profit_is_positive' => $kpis['profit'] >= 0,
            ],
            'revenue_by_source' => $this->labelMoney($this->reports->revenueBySource($from, $to)),
            'todays_bookings' => $this->todaysBookings(),
            'utilisation' => $this->utilisation($from, $to),
            'branches' => Branch::query()
                ->withCount('courts')
                ->orderBy('name')
                ->get(['id', 'name', 'status'])
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'status' => $branch->status,
                    'courts' => $branch->courts_count,
                ]),
        ]);
    }

    /**
     * Today's schedule, soonest first.
     *
     * Only bookings that still hold their slot are listed; a cancelled or
     * completed slot is history, not something the front desk has to act on.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function todaysBookings(): array
    {
        return Booking::query()
            ->with(['court:id,name', 'customer:id,first_name,last_name'])
            ->whereIn('status', array_column(BookingStatus::occupying(), 'value'))
            ->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'court' => $booking->court->name ?? 'Unassigned',
                'time' => $booking->timeRange(),
                'player' => $booking->customer
                    ? trim($booking->customer->first_name.' '.$booking->customer->last_name)
                    : 'Guest',
                'status' => [
                    'label' => $booking->status->label(),
                    'badgeClass' => $booking->status->badgeClass(),
                ],
            ])
            ->all();
    }

    /**
     * Court utilisation, highest first.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function utilisation(Carbon $from, Carbon $to): array
    {
        $rows = $this->reports->courtUtilisation($from, $to);

        return array_map(fn (string $name, float $pct): array => [
            'court' => $name,
            'percent' => $pct,
        ], array_keys($rows), $rows);
    }

    /**
     * Format a name => peso map for display.
     *
     * @param  array<string, float>  $amounts
     * @return array<int, array<string, string>>
     */
    protected function labelMoney(array $amounts): array
    {
        return array_map(
            fn (string $label, float $amount): array => [
                'label' => ucfirst($label),
                'amount' => Money::format($amount),
            ],
            array_keys($amounts),
            $amounts,
        );
    }
}

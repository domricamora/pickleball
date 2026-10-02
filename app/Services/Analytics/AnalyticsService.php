<?php

namespace App\Services\Analytics;

use App\Enums\BookingStatus;
use App\Enums\FunnelStep;
use App\Models\AnalyticsEvent;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Organization;
use Illuminate\Support\Carbon;

/**
 * Product analytics (plan.md §23).
 *
 * Two deliberate choices:
 *  - events are written directly, not through a queue, because losing an
 *    event to a queue outage would quietly corrupt the funnel;
 *  - nothing is identified. There is no raw IP, only a salted hash, and no
 *    free-text that could carry personal data into a reporting tool.
 */
class AnalyticsService
{
    /**
     * Record one funnel step.
     *
     * @param  array<string, mixed>  $properties
     */
    public function track(
        FunnelStep $step,
        ?Organization $organization = null,
        array $properties = [],
        ?string $sessionId = null,
        ?string $device = null,
        ?string $referrer = null,
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'organization_id' => $organization?->id,
            'session_id' => $sessionId,
            'name' => $step,
            'properties' => $properties ?: null,
            'device' => $device,
            'referrer' => $referrer !== null ? mb_substr($referrer, 0, 128) : null,
            'occurred_at' => now(),
        ]);
    }

    /**
     * The funnel, with counts and step-to-step conversion.
     *
     * Conversion is measured against the previous step rather than the first,
     * because a facility that gets lots of visitors but few bookings is a
     * different problem from one that gets few visitors.
     *
     * @return array<int, array{step: string, label: string, count: int, conversion: float|null}>
     */
    public function funnel(?Organization $organization = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30);
        $to ??= now();

        $rows = $this->baseQuery($organization, $from, $to)
            ->selectRaw('name, COUNT(*) AS total')
            ->groupBy('name')
            ->pluck('total', 'name');

        $steps = FunnelStep::cases();
        $out = [];
        $previous = null;

        foreach ($steps as $step) {
            $count = (int) ($rows[$step->value] ?? 0);

            /*
             * The first step has nothing before it, so it has no conversion.
             * A later step whose predecessor is zero is undefined, not 0% and
             * not 100% — but if the step itself has data it is worth reporting
             * as a full conversion, because that is what actually happened.
             */
            $conversion = match (true) {
                $previous === null => null,
                $previous === 0 && $count === 0 => null,
                $previous === 0 => 100.0,
                default => round(($count / $previous) * 100, 1),
            };

            $out[] = [
                'step' => $step->value,
                'label' => $step->label(),
                'count' => $count,
                'conversion' => $conversion,
            ];

            $previous = $count;
        }

        return $out;
    }

    /**
     * Where visitors came from, biggest first.
     *
     * @return array<string, int>
     */
    public function referrers(?Organization $organization = null, ?Carbon $from = null): array
    {
        $rows = $this->baseQuery($organization, $from ?? now()->subDays(30), now())
            ->where('name', FunnelStep::VISITOR->value)
            ->whereNotNull('referrer')
            ->selectRaw('referrer, COUNT(*) AS total')
            ->groupBy('referrer')
            ->orderByDesc('total')
            ->pluck('total', 'referrer');

        return $rows->map(fn ($v): int => (int) $v)->all();
    }

    /**
     * Court utilisation, so underused courts are visible (plan.md §23).
     *
     * @return array<string, float>
     */
    public function courtUtilisation(?Organization $organization = null, ?Carbon $from = null): array
    {
        $from ??= now()->subDays(30);
        $days = max(1, (int) $from->diffInDays(now()) + 1);

        $bookings = Booking::query()
            ->when($organization, fn ($q) => $q->where('organization_id', $organization->id))
            ->whereIn('status', [
                BookingStatus::COMPLETED->value,
                BookingStatus::CHECKED_IN->value,
            ])
            ->whereBetween('starts_at', [$from, now()])
            ->get(['court_id', 'duration_minutes']);

        $byCourt = [];

        foreach ($bookings as $booking) {
            $byCourt[$booking->court_id] = ($byCourt[$booking->court_id] ?? 0)
                + (int) $booking->duration_minutes;
        }

        $courts = Court::query()
            ->when($organization, fn ($q) => $q->where('organization_id', $organization->id))
            ->get(['id', 'name']);

        $out = [];

        foreach ($courts as $court) {
            $out[$court->name] = round((($byCourt[$court->id] ?? 0) / ($days * 14 * 60)) * 100, 1);
        }

        arsort($out);

        return $out;
    }

    /**
     * The shared, explicitly scoped base query.
     *
     * AnalyticsEvent has no global tenant scope by design, so scoping happens
     * here and is visible at the call site.
     */
    protected function baseQuery(?Organization $organization, Carbon $from, Carbon $to)
    {
        return AnalyticsEvent::query()
            ->when($organization, fn ($q) => $q->where('organization_id', $organization->id))
            ->whereBetween('occurred_at', [$from, $to]);
    }
}

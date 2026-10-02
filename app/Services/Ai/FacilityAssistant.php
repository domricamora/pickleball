<?php

namespace App\Services\Ai;

use App\Enums\BookingStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\MembershipSubscription;
use App\Models\Organization;
use App\Services\AvailabilityService;
use App\Services\Inventory\InventoryService;
use App\Services\Reports\ReportingService;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * The facility assistant (plan.md §22).
 *
 * Answers are computed from the facility's own data rather than generated, so
 * every answer is verifiable and cannot hallucinate a number. A language model
 * would sit in front of this class to phrase the result, not to invent it.
 *
 * Everything here is read-only. AiGuard is what actually forbids writes.
 */
class FacilityAssistant
{
    public function __construct(
        private readonly AiGuard $guard,
        private readonly AvailabilityService $availability,
        private readonly ReportingService $reports,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Answer a facility question.
     *
     * @return array{intent: string, answer: string, data: array<string, mixed>}
     */
    public function answer(Organization $organization, string $question): array
    {
        $this->guard->assertScope($organization);

        $intent = $this->classify($question);

        return match ($intent) {
            'availability' => $this->availabilityAnswer($organization, $question),
            'busiest_hours' => $this->busiestHours($organization),
            'memberships_expiring' => $this->expiringMemberships($organization),
            'revenue' => $this->revenue($organization),
            'inventory_risk' => $this->inventoryRisk($organization),
            default => [
                'intent' => 'unknown',
                'answer' => 'I can answer questions about court availability, busy '
                    .'hours, expiring memberships, revenue and stock levels.',
                'data' => [],
            ],
        };
    }

    /**
     * Which question is this?
     *
     * Matching is on word stems rather than whole keywords, because "busiest"
     * does not contain "busy" and a keyword list that misses common word forms
     * silently routes everything to the fallback answer. A wrong guess is safe
     * because the fallback is the "unknown" answer, never a wrong number.
     */
    protected function classify(string $question): string
    {
        $q = mb_strtolower($question);

        $has = fn (string ...$stems): bool => (bool) array_filter(
            $stems,
            fn (string $stem): bool => str_contains($q, $stem),
        );

        return match (true) {
            $has('availab', 'free', 'open slot') => 'availability',
            $has('busi', 'popular', 'peak hour') => 'busiest_hours',
            $has('expir', 'renew') => 'memberships_expiring',
            $has('revenue', 'sales', 'earn') => 'revenue',
            $has('stock', 'inventory', 'reorder') => 'inventory_risk',
            default => 'unknown',
        };
    }

    /**
     * @return array{intent: string, answer: string, data: array<string, mixed>}
     */
    protected function availabilityAnswer(Organization $organization, string $question): array
    {
        $today = Carbon::now();

        $courts = Court::query()
            ->where('organization_id', $organization->id)
            ->available()
            ->get();

        $open = 0;
        $summary = [];

        foreach ($courts as $court) {
            $slots = $this->availability->slotsFor($court, $today);
            $count = count($slots);

            if ($count > 0) {
                $open++;
            }

            $summary[] = [
                'court' => $court->name,
                'open_slots_today' => $count,
            ];
        }

        return [
            'intent' => 'availability',
            'answer' => $open === 0
                ? 'No courts have open slots today.'
                : "{$open} of {$courts->count()} courts have open slots today.",
            'data' => ['courts' => $summary],
        ];
    }

    /**
     * @return array{intent: string, answer: string, data: array<string, mixed>}
     */
    protected function busiestHours(Organization $organization): array
    {
        $bookings = Booking::query()
            ->where('organization_id', $organization->id)
            ->whereIn('status', [
                BookingStatus::COMPLETED->value,
                BookingStatus::CHECKED_IN->value,
            ])
            ->where('starts_at', '>=', now()->subDays(30))
            ->get(['starts_at']);

        $byHour = array_fill(0, 24, 0);

        foreach ($bookings as $booking) {
            $byHour[(int) $booking->starts_at->timezone(config('platform.locale.timezone'))->format('G')]++;
        }

        arsort($byHour);

        // With no bookings every hour is zero, and arsort would still hand
        // back the first three keys — making midnight look like a peak. Only
        // report a peak when something actually happened.
        $total = array_sum($byHour);
        $top = $total === 0 ? [] : array_slice($byHour, 0, 3, true);

        $label = $top === []
            ? 'No booking history in the last 30 days.'
            : 'Busiest hours: '.implode(', ', array_map(
                fn (int $hour): string => $this->hourLabel($hour),
                array_keys($top),
            )).'.';

        return [
            'intent' => 'busiest_hours',
            'answer' => $label,
            'data' => ['by_hour' => $byHour],
        ];
    }

    /**
     * @return array{intent: string, answer: string, data: array<string, mixed>}
     */
    protected function expiringMemberships(Organization $organization): array
    {
        $expiring = MembershipSubscription::query()
            ->where('organization_id', $organization->id)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereBetween('ends_on', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->with('customer')
            ->get();

        $names = $expiring->map(
            fn (MembershipSubscription $s): string => $s->customer?->fullName() ?? 'Member',
        )->all();

        return [
            'intent' => 'memberships_expiring',
            'answer' => $expiring->count() === 0
                ? 'No memberships expire in the next 30 days.'
                : count($names).' membership(s) expire within 30 days.',
            'data' => ['members' => $names, 'count' => $expiring->count()],
        ];
    }

    /**
     * @return array{intent: string, answer: string, data: array<string, mixed>}
     */
    protected function revenue(Organization $organization): array
    {
        $kpis = $this->reports->kpis(now()->startOfMonth(), now());

        return [
            'intent' => 'revenue',
            'answer' => Money::format($kpis['revenue'])
                .' of booking revenue so far this month, from '
                .$kpis['bookings'].' completed bookings.',
            'data' => $kpis,
        ];
    }

    /**
     * @return array{intent: string, answer: string, data: array<string, mixed>}
     */
    protected function inventoryRisk(Organization $organization): array
    {
        $low = $this->inventory->needsReordering()
            ->map(fn ($p): string => $p->name.' ('.$p->stock.' left)')
            ->all();

        return [
            'intent' => 'inventory_risk',
            'answer' => $low === []
                ? 'Everything is above its reorder level.'
                : count($low).' product(s) need reordering.',
            'data' => ['low_stock' => $low],
        ];
    }

    /**
     * "6 PM" rather than "18".
     */
    protected function hourLabel(int $hour): string
    {
        $suffix = $hour < 12 ? 'AM' : 'PM';
        $display = $hour % 12 === 0 ? 12 : $hour % 12;

        return $display.':00 '.$suffix;
    }
}

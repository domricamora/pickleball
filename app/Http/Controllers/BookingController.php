<?php

namespace App\Http\Controllers;

use App\Actions\Booking\BookCourt;
use App\Enums\CourtSurface;
use App\Models\Branch;
use App\Models\Court;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * The public booking funnel (plan.md §12, §36).
 *
 * Flow: Facility -> Court -> Date -> Time -> Confirm.
 */
class BookingController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Court picker for a branch and date.
     */
    public function show(Request $request): Response
    {
        $branchId = $request->integer('branch_id') ?: null;
        $date = $request->date('date') ?? now();

        $branches = Branch::query()
            ->where('status', 'active')
            ->withCount('courts')
            ->orderBy('name')
            ->get(['id', 'name', 'address_city', 'address_barangay']);

        $courts = [];

        if ($branchId !== null) {
            $courts = Court::query()
                ->available()
                ->inBranch($branchId)
                ->orderBy('name')
                ->get(['id', 'name', 'number', 'capacity', 'surface', 'setting'])
                ->map(fn (Court $court): array => $this->courtPayload($court, $date))
                ->values();
        }

        return Inertia::render('Book', [
            'seo' => [
                'title' => 'Book a Court — '.config('platform.name'),
                'description' => 'Book a pickleball court in seconds. Pick your facility, choose a time and pay.',
            ],
            'branches' => $branches,
            'courts' => $courts,
            'selectedBranchId' => $branchId,
            'selectedDate' => $date->toDateString(),
            'today' => now()->toDateString(),
            'currencySymbol' => config('platform.locale.currency_symbol'),
        ]);
    }

    /**
     * A court and its bookable slots, shaped for the page.
     *
     * @return array<string, mixed>
     */
    protected function courtPayload(Court $court, \DateTimeInterface $date): array
    {
        $day = Carbon::instance($date);
        $slots = [];

        foreach ($this->availability->slotsFor($court, $day) as $slot) {
            $startsAt = $slot['starts_at'];

            $slots[] = [
                'starts_at' => $startsAt->toIso8601String(),
                'ends_at' => $slot['ends_at']->toIso8601String(),
                'label' => $startsAt->format('g:i A'),
                'amount' => $slot['amount'],
            ];
        }

        return [
            'id' => $court->id,
            'name' => $court->name,
            'number' => $court->number,
            'capacity' => $court->capacity,
            // surface is cast to its enum; the UI wants the raw value.
            'surface' => $this->surfaceValue($court),
            'setting' => $court->setting,
            'slots' => $slots,
        ];
    }

    /**
     * The raw surface value, whether or not the enum cast resolved.
     */
    protected function surfaceValue(Court $court): string
    {
        /** @phpstan-ignore-next-line the enum cast is not visible to static analysis. */
        return $court->surface instanceof CourtSurface
            ? $court->surface->value
            : (string) $court->surface;
    }

    /**
     * Create the booking.
     *
     * The court and window are re-validated inside the transaction by
     * BookCourt, so a stale page can never force a double booking.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $court = Court::query()->available()->findOrFail($data['court_id']);

        try {
            $booking = app(BookCourt::class)->handle($court, [
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'user_id' => $request->user()?->id,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['starts_at' => $exception->getMessage()]);
        }

        return to_route('book.index', [
            'branch_id' => $court->branch_id,
            'date' => $booking->starts_at->timezone(config('platform.locale.timezone'))->toDateString(),
        ])->with('success', "Booked. Your reference is {$booking->reference}.");
    }
}

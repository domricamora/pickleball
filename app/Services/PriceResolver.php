<?php

namespace App\Services;

use App\Enums\BlockType;
use App\Enums\PriceType;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtPrice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Resolves which published rate applies to a court at a moment in time.
 *
 * Kept separate from the model so the rule is testable without a database and
 * so Phase 4's booking engine can reuse it unchanged (plan.md §11, §31).
 */
class PriceResolver
{
    /**
     * The winning rule for a court, or null when the facility has published
     * nothing that matches.
     *
     * Court-specific prices beat branch-wide ones; among equals the most
     * specific type wins (PriceType::priority()).
     */
    public function resolve(Court $court, CarbonInterface $date, ?int $hour = null): ?CourtPrice
    {
        $candidates = $this->candidates($court, $date, $hour);

        return $candidates->sortByDesc(fn (CourtPrice $price): array => [
            $price->court_id !== null ? 1 : 0,
            $price->type->priority(),
        ])->first();
    }

    /**
     * Every rule that matches, most specific first.
     *
     * @return Collection<int, CourtPrice>
     */
    public function candidates(Court $court, CarbonInterface $date, ?int $hour = null): Collection
    {
        return CourtPrice::query()
            ->active()
            ->where('branch_id', $court->branch_id)
            ->where(function ($query) use ($court): void {
                // A rule scoped to this court, or a branch-wide default.
                $query->where('court_id', $court->id)->orWhereNull('court_id');
            })
            ->get()
            ->filter(fn (CourtPrice $price): bool => $this->matches($price, $court, $date, $hour))
            ->values();
    }

    /**
     * Whether a published rate applies right now.
     *
     * Holidays are resolved from the tenant's own holiday blocks rather than a
     * national calendar, because Philippine holidays vary per locality and a
     * facility may close for a local fiesta (plan.md §11, §32).
     */
    protected function matches(CourtPrice $price, Court $court, CarbonInterface $date, ?int $hour): bool
    {
        if ($price->type === PriceType::HOLIDAY) {
            return $this->isHoliday($court, $date);
        }

        return $price->appliesTo($date, $hour);
    }

    /**
     * Whether the facility has a holiday block covering this date.
     */
    public function isHoliday(Court $court, CarbonInterface $date): bool
    {
        return CourtBlock::query()
            ->where('type', BlockType::HOLIDAY->value)
            ->where(function ($query) use ($court): void {
                // A whole-branch holiday, or one aimed at this specific court.
                $query->whereNull('court_id')->orWhere('court_id', $court->id);
            })
            ->where(function ($query) use ($court): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $court->branch_id);
            })
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->exists();
    }

    /**
     * The amount in pesos to charge for a booking.
     *
     * Returns null when the facility has no matching rule, so the caller must
     * refuse rather than guess a price.
     */
    public function amountFor(Court $court, CarbonInterface $date, ?int $hour = null): ?float
    {
        return $this->resolve($court, $date, $hour)?->amount;
    }

    /**
     * Rate for a member or a guest, falling back to the general rule.
     *
     * Member and guest rates never apply on their own (see CourtPrice::appliesTo),
     * so they are looked up explicitly here.
     */
    public function amountForAudience(Court $court, PriceType $audience, CarbonInterface $date, ?int $hour = null): ?float
    {
        $explicit = CourtPrice::query()
            ->active()
            ->where('branch_id', $court->branch_id)
            ->where('type', $audience->value)
            ->where(function ($query) use ($court): void {
                $query->where('court_id', $court->id)->orWhereNull('court_id');
            })
            ->get()
            // Prefer the most specific audience rate available.
            ->sortByDesc(fn (CourtPrice $price): int => $price->court_id !== null ? 1 : 0)
            ->first();

        if ($explicit !== null) {
            return (float) $explicit->amount;
        }

        return $this->amountFor($court, $date, $hour);
    }
}

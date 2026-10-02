<?php

namespace App\Models;

use App\Enums\PriceType;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\CourtPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A published rate in pesos (plan.md §11, §13, §32).
 *
 * Amounts are pesos, never centavos, and always rendered through the shared
 * peso formatter. Tax treatment is administrative on the organization.
 *
 * @property PriceType $type
 * @property float $amount
 */
#[Fillable([
    'organization_id',
    'court_id',
    'branch_id',
    'type',
    'amount',
    'min_minutes',
    'increment_minutes',
    'starts_at_hour',
    'ends_at_hour',
    'is_active',
])]
class CourtPrice extends Model
{
    /** @use HasFactory<CourtPriceFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PriceType::class,
            'amount' => 'decimal:2',
            'min_minutes' => 'integer',
            'increment_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<CourtPrice>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Whether this rate applies to a given day and hour.
     *
     * The most specific matching rule wins, so a peak rate beats a plain
     * weekday rate (see PriceType::priority()).
     */
    public function appliesTo(CarbonInterface $date, ?int $hour = null): bool
    {
        $weekday = (int) $date->dayOfWeek;
        $isWeekend = $weekday === 0 || $weekday === 6;

        $withinPeakWindow = $this->starts_at_hour !== null
            && $this->ends_at_hour !== null
            && $hour !== null
            && $hour >= $this->starts_at_hour
            && $hour < $this->ends_at_hour;

        return match ($this->type) {
            PriceType::WEEKEND => $isWeekend,
            PriceType::WEEKDAY => ! $isWeekend,
            PriceType::PEAK => $withinPeakWindow,
            PriceType::OFF_PEAK => $hour !== null && $hour < 17,
            /*
             * Holiday, member and guest rates are never inferred from the
             * clock. Whether a date is a holiday depends on the facility's own
             * calendar — Philippine holidays vary per locality and a facility
             * may close for a local fiesta — so it is resolved against the
             * tenant's holiday blocks by PriceResolver, not guessed here.
             */
            PriceType::HOLIDAY, PriceType::MEMBER, PriceType::GUEST => false,
        };
    }
}

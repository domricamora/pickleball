<?php

namespace App\Models;

use App\Enums\BlockType;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\CourtBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A window during which a court (or a whole branch) cannot be booked.
 *
 * Blocks override the weekly schedule and are one of the inputs to availability
 * in Phase 4 (plan.md §11, §31).
 *
 * @property BlockType $type
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property string|null $starts_at H:i:s, null = all day
 * @property string|null $ends_at
 */
#[Fillable([
    'organization_id',
    'court_id',
    'branch_id',
    'type',
    'reason',
    'starts_on',
    'ends_on',
    'starts_at',
    'ends_at',
])]
class CourtBlock extends Model
{
    /** @use HasFactory<CourtBlockFactory> */
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
            'type' => BlockType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * Blocks that overlap the given date range.
     *
     * @param  Builder<CourtBlock>  $query
     */
    public function scopeOverlapping(Builder $query, string $from, string $to): void
    {
        $query->whereDate('starts_on', '<=', $to)
            ->whereDate('ends_on', '>=', $from);
    }

    /**
     * Whether this block stops a given slot being booked.
     *
     * A null time window means the whole day is blocked.
     */
    public function blocksSlot(CarbonInterface $date, string $time): bool
    {
        $day = $date->toDateString();

        if ($day < $this->starts_on->toDateString() || $day > $this->ends_on->toDateString()) {
            return false;
        }

        if ($this->starts_at === null || $this->ends_at === null) {
            return true;
        }

        $time = substr($time, 0, 5);

        return $time >= substr($this->starts_at, 0, 5) && $time < substr($this->ends_at, 0, 5);
    }
}

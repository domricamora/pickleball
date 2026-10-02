<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CourtScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A court's recurring weekly opening hours (plan.md §11).
 *
 * @property int $weekday 0 = Sunday ... 6 = Saturday
 * @property string $opens_at H:i:s
 * @property string $closes_at H:i:s
 * @property bool $is_closed
 */
#[Fillable(['organization_id', 'court_id', 'weekday', 'opens_at', 'closes_at', 'is_closed'])]
class CourtSchedule extends Model
{
    /** @use HasFactory<CourtScheduleFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * Whether the given "HH:MM" falls inside this window.
     *
     * A closing time earlier than the opening time is treated as crossing
     * midnight, which is how a late-night court session is expressed.
     */
    public function covers(string $time): bool
    {
        if ($this->is_closed) {
            return false;
        }

        $time = substr($time, 0, 5);

        if ($this->opens_at <= $this->closes_at) {
            return $time >= substr($this->opens_at, 0, 5) && $time < substr($this->closes_at, 0, 5);
        }

        return $time >= substr($this->opens_at, 0, 5) || $time < substr($this->closes_at, 0, 5);
    }

    public function weekdayName(): string
    {
        return config('platform.weekdays')[(int) $this->weekday] ?? 'Day';
    }
}

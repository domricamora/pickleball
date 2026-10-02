<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A skill or format division within an event, e.g. "3.0 Mixed" (plan.md §16).
 *
 * Reached through its event, so it carries no organization_id of its own.
 */
#[Fillable(['event_id', 'name', 'min_rating', 'max_rating', 'capacity'])]
class EventDivision extends Model
{
    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_rating' => 'float',
            'max_rating' => 'float',
            'capacity' => 'integer',
        ];
    }

    /**
     * Whether a given DUPR-style rating belongs in this division.
     */
    public function accepts(float $rating): bool
    {
        if ($this->min_rating !== null && $rating < (float) $this->min_rating) {
            return false;
        }

        return ! ($this->max_rating !== null && $rating > (float) $this->max_rating);
    }
}

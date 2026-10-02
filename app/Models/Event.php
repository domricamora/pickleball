<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An open play session, clinic, league or tournament (plan.md §16).
 *
 * @property EventStatus $status
 * @property EventType $type
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'name',
    'description',
    'type',
    'status',
    'starts_at',
    'ends_at',
    'court_id',
    'capacity',
    'fee',
    'currency',
    'format',
    'is_members_only',
    'max_team_size',
    'notes',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<EventDivision, $this>
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(EventDivision::class);
    }

    /**
     * @return HasMany<EventMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'fee' => 'decimal:2',
            'capacity' => 'integer',
            'max_team_size' => 'integer',
            'is_members_only' => 'boolean',
        ];
    }

    /**
     * Players who hold a confirmed place.
     *
     * Waitlisted players deliberately do NOT count: they have no place yet, and
     * counting them here would make a withdrawn place look still-full and stop
     * the waitlist from being promoted.
     */
    public function confirmedRegistrations(): int
    {
        return $this->registrations()
            ->where('status', 'registered')
            ->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->confirmedRegistrations() >= $this->capacity;
    }

    public function hasSpace(): bool
    {
        return ! $this->isFull();
    }

    public function formattedFee(): string
    {
        return (float) $this->fee === 0.0 ? 'Free' : Money::format($this->fee);
    }

    /**
     * "Sat, 14 Mar 2026 · 9:00 AM – 1:00 PM" in Manila time.
     */
    public function schedule(): string
    {
        $tz = config('platform.locale.timezone');

        /** @phpstan-ignore-next-line the datetime cast is not visible statically. */
        return $this->starts_at->timezone($tz)->format('D, d M Y · g:i A')
            /** @phpstan-ignore-next-line */
            .' – '.$this->ends_at->timezone($tz)->format('g:i A');
    }
}

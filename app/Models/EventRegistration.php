<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One player's (or team's) entry into an event (plan.md §16).
 *
 * A doubles player is a single registration carrying a partner name, which is
 * why a match can treat both sides uniformly.
 */
#[Fillable([
    'event_id',
    'event_division_id',
    'customer_id',
    'user_id',
    'partner_name',
    'team_name',
    'status',
    'amount_paid',
    'notes',
])]
class EventRegistration extends Model
{
    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<EventDivision, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(EventDivision::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount_paid' => 'decimal:2'];
    }

    public function holdsAPlace(): bool
    {
        return in_array($this->status, ['registered', 'waitlisted'], true);
    }

    /**
     * The name to show in a bracket: a team name, else the player.
     */
    public function displayName(): string
    {
        if ($this->team_name) {
            return $this->team_name;
        }

        $player = $this->customer?->fullName() ?? 'Player';

        return $this->partner_name ? "{$player} / {$this->partner_name}" : $player;
    }
}

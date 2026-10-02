<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * A match in a bracket or round robin (plan.md §16).
 *
 * The two sides are registrations, so singles, doubles and mixed doubles all
 * use the same row shape.
 */
#[Fillable([
    'event_id',
    'event_division_id',
    'winner_of_match_id',
    'round',
    'position',
    'team_a_registration_id',
    'team_b_registration_id',
    'team_a_score',
    'team_b_score',
    'status',
    'best_of',
    'court_id',
    'scheduled_at',
    'notes',
])]
class EventMatch extends Model
{
    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function teamA(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'team_a_registration_id');
    }

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function teamB(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'team_b_registration_id');
    }

    /**
     * @return BelongsTo<EventMatch, $this>
     */
    public function winnerOf(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'winner_of_match_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_a_score' => 'integer',
            'team_b_score' => 'integer',
            'round' => 'integer',
            'position' => 'integer',
            'best_of' => 'integer',
            'scheduled_at' => 'datetime',
        ];
    }

    public function isBye(): bool
    {
        return $this->status === 'bye';
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * The winning registration, or null while the match is unplayed.
     */
    public function winner(): ?EventRegistration
    {
        if (! $this->isComplete() || $this->team_a_score === null || $this->team_b_score === null) {
            return null;
        }

        if ($this->team_a_score === $this->team_b_score) {
            throw new RuntimeException('A completed match cannot be a draw.');
        }

        return $this->team_a_score > $this->team_b_score ? $this->teamA : $this->teamB;
    }

    public function scoreLine(): string
    {
        if ($this->team_a_score === null || $this->team_b_score === null) {
            return 'v';
        }

        return "{$this->team_a_score}–{$this->team_b_score}";
    }

    /**
     * Whether a given score is enough to win a best-of-N match.
     */
    public function isWinningScore(int $score): bool
    {
        $needed = (int) ceil($this->best_of / 2);

        return $score >= $needed;
    }
}

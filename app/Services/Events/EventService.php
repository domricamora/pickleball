<?php

namespace App\Services\Events;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMatch;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Event registration and match results (plan.md §16).
 *
 * Registration locks the event row so two people cannot both take the last
 * place. Match scoring locks the match row so a referee tapping "save" twice
 * cannot record two different results.
 */
class EventService
{
    /**
     * Register a customer for an event.
     */
    public function register(
        Event $event,
        Customer $customer,
        ?EventDivision $division = null,
        ?string $partnerName = null,
    ): EventRegistration {
        return DB::transaction(function () use ($event, $customer, $division, $partnerName): EventRegistration {
            // Serialise registration attempts on this event.
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->acceptsRegistrations()) {
                throw new RuntimeException("This event is {$locked->status->label()} and is not accepting entries.");
            }

            if ($locked->is_members_only && ! $this->customerIsMember($customer)) {
                throw new RuntimeException('This event is for members only.');
            }

            $alreadyIn = $locked->registrations()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['registered', 'waitlisted'])
                ->exists();

            if ($alreadyIn) {
                throw new RuntimeException('This player is already registered for this event.');
            }

            // Over capacity the player waits rather than being turned away.
            $status = $locked->isFull() ? 'waitlisted' : 'registered';

            return $locked->registrations()->create([
                'customer_id' => $customer->id,
                'event_division_id' => $division?->id,
                'partner_name' => $partnerName,
                'status' => $status,
                'amount_paid' => $status === 'registered' ? $locked->fee : 0,
            ]);
        }, 3);
    }

    /**
     * Withdraw a registration, freeing its place for the waitlist.
     */
    public function withdraw(EventRegistration $registration): EventRegistration
    {
        return DB::transaction(function () use ($registration): EventRegistration {
            $locked = EventRegistration::query()
                ->whereKey($registration->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wasRegistered = $locked->status === 'registered';

            $locked->status = 'withdrawn';
            $locked->save();

            if ($wasRegistered) {
                $this->promoteFromWaitlist($locked->event);
            }

            return $locked;
        }, 3);
    }

    /**
     * Move the first waiting player into the freed place.
     */
    protected function promoteFromWaitlist(Event $event): void
    {
        $next = $event->registrations()
            ->where('status', 'waitlisted')
            ->orderBy('id')
            ->first();

        if ($next === null || $event->isFull()) {
            return;
        }

        $next->update([
            'status' => 'registered',
            'amount_paid' => $event->fee,
        ]);
    }

    /**
     * Record a match result.
     *
     * @throws RuntimeException on a draw or a score that cannot end the match
     */
    public function recordResult(EventMatch $match, int $teamAScore, int $teamBScore): EventMatch
    {
        if ($match->isBye()) {
            throw new RuntimeException('A bye cannot be scored.');
        }

        if ($teamAScore === $teamBScore) {
            throw new RuntimeException('A match cannot end in a draw.');
        }

        return DB::transaction(function () use ($match, $teamAScore, $teamBScore): EventMatch {
            $locked = EventMatch::query()
                ->whereKey($match->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isComplete()) {
                throw new RuntimeException('This match has already been recorded.');
            }

            $winnerScore = max($teamAScore, $teamBScore);
            $gamesPlayed = $teamAScore + $teamBScore;

            // The match stops the instant a side reaches the winning score, so
            // the loser can never have won more games than the winner: 2-0,
            // 2-1 and 1-0 are all reachable in a best of three, but 1-2 is not,
            // because the match would already have ended at 2-1.
            if ($gamesPlayed > $locked->best_of) {
                throw new RuntimeException('That score cannot be reached in a best-of match.');
            }

            if (min($teamAScore, $teamBScore) >= $winnerScore) {
                throw new RuntimeException('That match had already ended before this game.');
            }

            if (! $locked->isWinningScore($winnerScore)) {
                throw new RuntimeException(
                    "A best-of-{$locked->best_of} match cannot be won {$winnerScore}–"
                    .min($teamAScore, $teamBScore).'.'
                );
            }

            $locked->team_a_score = $teamAScore;
            $locked->team_b_score = $teamBScore;
            $locked->status = 'completed';
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * Award a win to one side without playing, e.g. a no-show.
     */
    public function recordWalkover(EventMatch $match, EventRegistration $winner): EventMatch
    {
        return DB::transaction(function () use ($match, $winner): EventMatch {
            $locked = EventMatch::query()->whereKey($match->id)->lockForUpdate()->firstOrFail();

            $locked->team_a_registration_id = $winner->id;
            $locked->team_b_score = 0;
            $locked->team_a_score = $locked->isWinningScore(1) ? 1 : 0;
            $locked->status = 'walkover';
            $locked->save();

            return $locked;
        }, 3);
    }

    /**
     * The event's leaderboard for a division, best record first.
     *
     * @return array<int, array{registration: EventRegistration, wins: int, losses: int, points: int}>
     */
    public function standings(EventDivision $division): array
    {
        $matches = $division->event->matches()
            ->where('event_division_id', $division->id)
            ->whereIn('status', ['completed', 'walkover'])
            ->with(['teamA', 'teamB'])
            ->get();

        $table = [];

        foreach ($matches as $match) {
            foreach (['teamA' => $match->team_a_score, 'teamB' => $match->team_b_score] as $side => $score) {
                $registration = $match->{$side};

                if ($registration === null) {
                    continue;
                }

                $table[$registration->id] ??= [
                    'registration' => $registration,
                    'wins' => 0,
                    'losses' => 0,
                    'points' => 0,
                ];
            }

            $winner = $match->winner();

            if ($winner === null) {
                continue;
            }

            $table[$winner->id]['wins']++;
            $table[$winner->id]['points'] += 3;

            $loserId = $winner->id === $match->team_a_registration_id
                ? $match->team_b_registration_id
                : $match->team_a_registration_id;

            if ($loserId !== null && isset($table[$loserId])) {
                $table[$loserId]['losses']++;
            }
        }

        uasort($table, fn (array $a, array $b): int => $b['points'] <=> $a['points']
            ?: $b['wins'] <=> $a['wins']);

        return array_values($table);
    }

    /**
     * Whether the customer holds an active membership at this facility.
     */
    protected function customerIsMember(Customer $customer): bool
    {
        return $customer->subscriptions()->usable()->exists();
    }
}

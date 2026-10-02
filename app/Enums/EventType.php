<?php

namespace App\Enums;

/**
 * Kinds of event a facility runs (plan.md §16).
 */
enum EventType: string
{
    case OPEN_PLAY = 'open_play';
    case BEGINNER_SESSION = 'beginner_session';
    case CLINIC = 'clinic';
    case COACHING = 'coaching';
    case LEAGUE = 'league';
    case TOURNAMENT = 'tournament';
    case COMMUNITY = 'community';

    /**
     * Whether the event is bracket-driven and needs divisions and results.
     */
    public function isCompetitive(): bool
    {
        return in_array($this, [self::TOURNAMENT, self::LEAGUE], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::OPEN_PLAY => 'Open play',
            self::BEGINNER_SESSION => 'Beginner session',
            self::CLINIC => 'Clinic',
            self::COACHING => 'Coaching',
            self::LEAGUE => 'League',
            self::TOURNAMENT => 'Tournament',
            self::COMMUNITY => 'Community event',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

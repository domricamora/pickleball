<?php

namespace Database\Seeders\Concerns;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;

/**
 * A programme of events: past, live and upcoming.
 *
 * Mixed statuses matter because the events screen sorts by start date and the
 * marketing pages list what a visitor can actually join. A single status would
 * make either the archive or the "join now" list look wrong (plan.md §16).
 */
trait SeedsEvents
{
    private function seedEvents(int $orgId, int $branchId, ?int $courtId): void
    {
        // Positional rows: name, type, offsetDays, hour, capacity, fee, membersOnly.
        $programme = [
            ['Tuesday Night Open Play', EventType::OPEN_PLAY, -70, 18, 32, 350.00, false],
            ['Beginner Clinic -- Grip and Footwork', EventType::BEGINNER_SESSION, -45, 10, 24, 800.00, false],
            ['Makati Open Singles Tournament', EventType::TOURNAMENT, -21, 8, 64, 2500.00, true],
            ['Corporate Doubles Ladder', EventType::LEAGUE, -10, 18, 32, 1800.00, true],
            ['Coaching Session with Coach Dave', EventType::COACHING, -2, 16, 8, 1200.00, false],
            ['Weekend Open Play', EventType::OPEN_PLAY, 3, 9, 40, 450.00, false],
            ['Advanced Clinic -- Serve and Return', EventType::CLINIC, 9, 15, 16, 1500.00, false],
            ['Community Play Day', EventType::COMMUNITY, 16, 8, 64, 0.00, false],
            ['Autumn Invitational Doubles', EventType::TOURNAMENT, 30, 8, 48, 2200.00, true],
        ];

        foreach ($programme as $row) {
            // Positional: name, type, offsetDays, hour, capacity, fee,
            // membersOnly. Destructured explicitly so a malformed row fails
            // here rather than as "undefined array key" further down.
            [$name, $type, $offsetDays, $hour, $capacity, $fee, $membersOnly] = $row;
            $startsAt = now()->addDays($offsetDays)->setTime($hour, 0);

            $status = match (true) {
                $startsAt->isPast() => EventStatus::COMPLETED,
                $capacity <= 16 && $offsetDays <= 10 => EventStatus::FULL,
                default => EventStatus::OPEN,
            };

            Event::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'name' => $name,
                'description' => $name.' at PicklePlay Makati. All levels welcome; courts and balls provided.',
                'type' => $type->value,
                'status' => $status->value,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHours($type->isCompetitive() ? 6 : 3),
                'court_id' => $courtId,
                'capacity' => $capacity,
                'fee' => number_format($fee, 2, '.', ''),
                'currency' => 'PHP',
                // The format column is a database enum of singles/doubles/mixed_doubles,
                // so a non-bracket event is played as doubles.
                'format' => $type->isCompetitive() ? 'mixed_doubles' : 'doubles',
                'is_members_only' => $membersOnly,
                'max_team_size' => $type->isCompetitive() ? 2 : 4,
                'notes' => $fee > 0 ? 'Fee payable at the front desk. Members receive a discount.' : 'Free for members and first-time visitors.',
            ]);
        }
    }
}

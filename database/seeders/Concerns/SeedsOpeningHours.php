<?php

namespace Database\Seeders\Concerns;

use App\Models\CourtSchedule;

/**
 * Opening hours for every court, in Manila local time.
 *
 * The booking engine reads these to decide what is bookable, so without them
 * the calendar has no slots at all and /book renders an empty state.
 */
trait SeedsOpeningHours
{
    /**
     * @param  array<int, int>  $courtIds
     */
    private function seedOpeningHours(int $orgId, array $courtIds): void
    {
        // 06:00-22:00 six days a week, closed Sunday -- a plausible club
        // timetable. Weekday numbers are 0 (Sunday) to 6 (Saturday).
        foreach ($courtIds as $courtId) {
            for ($weekday = 0; $weekday < 7; $weekday++) {
                $isClosed = $weekday === 0;

                // The columns are NOT NULL, so a closed day still carries
                // opening hours; is_closed is what the booking engine reads.
                CourtSchedule::create([
                    'organization_id' => $orgId,
                    'court_id' => $courtId,
                    'weekday' => $weekday,
                    'opens_at' => '06:00:00',
                    'closes_at' => '22:00:00',
                    'is_closed' => $isClosed,
                ]);
            }
        }
    }
}

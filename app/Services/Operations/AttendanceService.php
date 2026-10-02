<?php

namespace App\Services\Operations;

use App\Enums\LeaveStatus;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffLeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Attendance and leave (plan.md §19).
 *
 * A shift is a punch pair, and a person can only have one shift a day. The
 * unique (staff, work_date) index is the backstop for a double punch-in; the
 * row lock in punchOut is what makes the pair consistent.
 */
class AttendanceService
{
    /**
     * Record a punch-in for today.
     */
    public function punchIn(Staff $staff, ?Carbon $at = null): StaffAttendance
    {
        return DB::transaction(function () use ($staff, $at): StaffAttendance {
            $at ??= now();
            $date = $at->toDateString();

            $existing = StaffAttendance::query()
                ->where('staff_id', $staff->id)
                ->whereDate('work_date', $date)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new RuntimeException(
                    $staff->fullName().' has already punched in for this day.'
                );
            }

            return StaffAttendance::create([
                'organization_id' => $staff->organization_id,
                'staff_id' => $staff->id,
                'branch_id' => $staff->branch_id,
                'work_date' => $date,
                'time_in' => $at,
            ]);
        }, 3);
    }

    /**
     * Close the shift.
     *
     * @throws RuntimeException when there is no open shift to close
     */
    public function punchOut(Staff $staff, ?Carbon $at = null, int $breakMinutes = 0): StaffAttendance
    {
        return DB::transaction(function () use ($staff, $at, $breakMinutes): StaffAttendance {
            $at ??= now();
            $date = $at->toDateString();

            $shift = StaffAttendance::query()
                ->where('staff_id', $staff->id)
                ->whereDate('work_date', $date)
                ->lockForUpdate()
                ->first();

            if ($shift === null) {
                throw new RuntimeException($staff->fullName().' has not punched in today.');
            }

            if ($shift->time_out !== null) {
                throw new RuntimeException('This shift has already been closed.');
            }

            if ($at->lessThan($shift->time_in)) {
                throw new RuntimeException('A shift cannot end before it began.');
            }

            $shift->time_out = $at;
            $shift->break_minutes = max(0, $breakMinutes);
            $shift->save();

            return $shift;
        }, 3);
    }

    /**
     * Total paid hours for a staff member between two dates.
     */
    public function totalHours(Staff $staff, Carbon $from, Carbon $to): float
    {
        $minutes = (int) StaffAttendance::query()
            ->where('staff_id', $staff->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('time_out')
            ->get()
            ->sum(fn (StaffAttendance $a): int => (int) $a->workedMinutes());

        return round($minutes / 60, 2);
    }

    /**
     * Request time off.
     */
    public function requestLeave(Staff $staff, Carbon $startsOn, Carbon $endsOn, ?string $reason = null): StaffLeaveRequest
    {
        if ($endsOn->lessThan($startsOn)) {
            throw new RuntimeException('Leave cannot end before it starts.');
        }

        return StaffLeaveRequest::create([
            'organization_id' => $staff->organization_id,
            'staff_id' => $staff->id,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'reason' => $reason,
            'status' => LeaveStatus::PENDING,
        ]);
    }

    /**
     * Approve or reject a request. Locks the row so two managers cannot
     * both decide the same request.
     */
    public function reviewLeave(StaffLeaveRequest $request, bool $approve, ?User $reviewer = null, ?string $note = null): StaffLeaveRequest
    {
        return DB::transaction(function () use ($request, $approve, $reviewer, $note): StaffLeaveRequest {
            $locked = StaffLeaveRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isPendingReview()) {
                throw new RuntimeException("This request was already {$locked->status->label()}.");
            }

            $locked->status = $approve ? LeaveStatus::APPROVED : LeaveStatus::REJECTED;
            $locked->review_note = $note;
            $locked->reviewed_by = $reviewer?->id;
            $locked->reviewed_at = now();
            $locked->save();

            return $locked;
        }, 3);
    }
}

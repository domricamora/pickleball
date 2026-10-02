<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One shift: a punch-in and, later, a punch-out (plan.md §19).
 *
 * Storing the pair rather than an accumulated hours figure keeps a missing or
 * corrected punch visible instead of hiding it inside a total.
 *
 * @property Carbon|null $time_in
 * @property Carbon|null $time_out
 */
#[Fillable([
    'organization_id',
    'staff_id',
    'branch_id',
    'work_date',
    'time_in',
    'time_out',
    'break_minutes',
    'notes',
])]
class StaffAttendance extends Model
{
    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'break_minutes' => 'integer',
        ];
    }

    public function isComplete(): bool
    {
        return $this->time_in !== null && $this->time_out !== null;
    }

    /**
     * Paid hours worked, excluding the break. Null while still on shift.
     */
    public function workedMinutes(): ?int
    {
        if (! $this->isComplete()) {
            return null;
        }

        $minutes = (int) abs($this->time_in->diffInMinutes($this->time_out));

        return max(0, $minutes - (int) $this->break_minutes);
    }

    public function workedHours(): ?float
    {
        $minutes = $this->workedMinutes();

        return $minutes === null ? null : round($minutes / 60, 2);
    }
}

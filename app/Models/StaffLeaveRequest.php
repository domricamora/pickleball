<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A staff member's request for time off (plan.md §19).
 *
 * @property LeaveStatus $status
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 */
#[Fillable([
    'organization_id',
    'staff_id',
    'reviewed_by',
    'starts_on',
    'ends_on',
    'reason',
    'status',
    'review_note',
    'reviewed_at',
])]
class StaffLeaveRequest extends Model
{
    use BelongsToOrganization;

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
            'status' => LeaveStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * How many days are being taken, counting both ends.
     */
    public function days(): int
    {
        // Carbon 4 returns a float from diffInDays.
        return (int) $this->starts_on->diffInDays($this->ends_on) + 1;
    }
}

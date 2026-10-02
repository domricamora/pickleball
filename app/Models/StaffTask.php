<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A piece of work handed to a member of staff (plan.md §19).
 *
 * @property Carbon|null $due_on
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'assigned_to',
    'assigned_by',
    'title',
    'description',
    'type',
    'priority',
    'status',
    'due_on',
    'completed_at',
])]
class StaffTask extends Model
{
    use BelongsToOrganization;

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'done'
            && $this->status !== 'cancelled'
            && $this->due_on !== null
            && $this->due_on->isPast();
    }
}

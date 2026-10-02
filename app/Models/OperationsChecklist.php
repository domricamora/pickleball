<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An opening or closing routine for a branch on a given day (plan.md §19).
 *
 * Both types share one table because they have the same shape; a separate
 * "is_complete" flag would only duplicate completed_at.
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'staff_id',
    'completed_by',
    'type',
    'performed_on',
    'items',
    'notes',
    'completed_at',
])]
class OperationsChecklist extends Model
{
    use BelongsToOrganization;

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

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
            'performed_on' => 'date',
            'items' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * How many checklist items are ticked off.
     *
     * @return array{done: int, total: int}
     */
    public function progress(): array
    {
        $items = $this->items ?? [];

        // Items are stored with a "done" key, so read it directly; anything
        // falsy (false, null, 0) counts as not done.
        $done = count(array_filter(
            $items,
            fn ($item): bool => (bool) $item['done'],
        ));

        return ['done' => $done, 'total' => count($items)];
    }
}

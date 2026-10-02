<?php

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that went wrong on court or in the facility (plan.md §19).
 *
 * @property IncidentSeverity $severity
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'court_id',
    'reported_by',
    'customer_id',
    'title',
    'description',
    'severity',
    'status',
    'resolved_at',
    'resolution',
])]
class IncidentReport extends Model
{
    use BelongsToOrganization;

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity' => IncidentSeverity::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        // Default to open when the column has not been read yet: a freshly
        // created instance has null here, not the database default.
        return in_array($this->status ?? 'open', ['open', 'investigating'], true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One recorded action (plan.md §24).
 *
 * Append-only. There is no updated_at because a log that can be edited is not
 * a log, and the model offers no update or delete path.
 */
#[Fillable([
    'organization_id',
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'changes',
    'ip_hash',
    'user_agent',
    'created_at',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * A log is immutable once written.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        return false;
    }

    public function delete(): ?bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

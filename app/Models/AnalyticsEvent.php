<?php

namespace App\Models;

use App\Enums\FunnelStep;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One tracked product event (plan.md §23).
 *
 * Deliberately NOT tenant-scoped: a visitor exists before choosing a
 * facility, so organization_id is nullable. Reading one facility's analytics
 * filters on it explicitly in AnalyticsService.
 *
 * @property FunnelStep $name
 */
#[Fillable([
    'organization_id',
    'user_id',
    'customer_id',
    'session_id',
    'name',
    'properties',
    'device',
    'referrer',
    'ip_hash',
    'occurred_at',
])]
class AnalyticsEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => FunnelStep::class,
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->occurred_at ??= now();
        });
    }
}

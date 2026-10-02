<?php

namespace App\Models;

use App\Enums\CourtStatus;
use App\Enums\CourtSurface;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A bookable court at a branch (plan.md §11).
 *
 * @property int $id
 * @property int $organization_id
 * @property int $branch_id
 * @property CourtStatus $status
 * @property int $capacity
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'name',
    'number',
    'surface',
    'type',
    'setting',
    'status',
    'capacity',
    'amenities',
    'notes',
])]
class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<CourtSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(CourtSchedule::class);
    }

    /**
     * @return HasMany<CourtBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(CourtBlock::class);
    }

    /**
     * @return HasMany<CourtPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(CourtPrice::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CourtStatus::class,
            'surface' => CourtSurface::class,
            'capacity' => 'integer',
            'amenities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Only an available court can be booked (plan.md §31).
     */
    public function isBookable(): bool
    {
        return $this->status->isBookable();
    }

    /**
     * @param  Builder<Court>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', CourtStatus::AVAILABLE->value);
    }

    /**
     * @param  Builder<Court>  $query
     */
    public function scopeInBranch(Builder $query, int $branchId): void
    {
        $query->where('branch_id', $branchId);
    }

    public function displayName(): string
    {
        return $this->number ? "{$this->name} (#{$this->number})" : $this->name;
    }
}

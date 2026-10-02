<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A physical location operated by an organization.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $status
 */
#[Fillable([
    'organization_id',
    'name',
    'code',
    'status',
    'address_street',
    'address_barangay',
    'address_city',
    'address_province',
    'address_region',
    'address_postal_code',
    'address_country',
    'email',
    'phone',
    'latitude',
    'longitude',
    'is_primary',
])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return HasMany<Court, $this>
     */
    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Philippine address rendered on one line (plan.md §32).
     */
    public function fullAddress(): string
    {
        return collect([
            $this->address_street,
            $this->address_barangay,
            $this->address_city,
            $this->address_province,
            $this->address_postal_code,
        ])->filter()->implode(', ');
    }
}

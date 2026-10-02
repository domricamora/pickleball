<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A tenant: one facility business, potentially with several branches.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $status
 */
#[Fillable([
    'name',
    'slug',
    'status',
    'business_name',
    'tin',
    'email',
    'phone',
    'address_street',
    'address_barangay',
    'address_city',
    'address_province',
    'address_region',
    'address_postal_code',
    'address_country',
    'timezone',
    'currency',
    'tax_rate',
    'tax_inclusive',
    'settings',
    'trial_ends_at',
    'subscription_ends_at',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Slug is generated from the name unless the caller supplies one.
     */
    protected static function booted(): void
    {
        static::creating(function (self $organization): void {
            $organization->slug ??= static::uniqueSlug($organization->name);
        });
    }

    /**
     * Build a slug that does not collide with an existing tenant.
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
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
     * Tax is administrative configuration, never hard-coded (plan.md §32).
     */
    public function taxRate(): float
    {
        return (float) $this->tax_rate;
    }
}

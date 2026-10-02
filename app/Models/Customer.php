<?php

namespace App\Models;

use App\Enums\CustomerSegment;
use App\Enums\PreferredPlayingTime;
use App\Enums\SkillLevel;
use App\Models\Concerns\BelongsToOrganization;
use App\Services\Customers\CustomerSegmenter;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * A player known to a facility (plan.md §14).
 *
 * A customer is tenant-owned: two facilities may both know "Juan Dela Cruz"
 * without sharing anything.
 *
 * @property SkillLevel $skill_level
 * @property PreferredPlayingTime $preferred_playing_time
 */
#[Fillable([
    'organization_id',
    'user_id',
    'first_name',
    'last_name',
    'email',
    'mobile',
    'birthday',
    'gender',
    'address_line',
    'address_barangay',
    'address_city',
    'address_province',
    'emergency_contact_name',
    'emergency_contact_mobile',
    'skill_level',
    'preferred_playing_time',
    'favorite_surface',
    'notes',
    'consents_to_marketing',
    'consents_to_sms',
    'consented_at',
    'is_vip',
    'is_active',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $builder) use ($like): void {
            $builder->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('mobile', 'like', $like);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skill_level' => SkillLevel::class,
            'preferred_playing_time' => PreferredPlayingTime::class,
            'birthday' => 'date',
            'consented_at' => 'datetime',
            'consents_to_marketing' => 'boolean',
            'consents_to_sms' => 'boolean',
            'is_vip' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * "Juan Dela Cruz"
     */
    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Age in whole years, or null when no birthday is recorded.
     */
    public function age(): ?int
    {
        /** @phpstan-ignore-next-line the date cast is not visible statically. */
        return $this->birthday?->age;
    }

    /**
     * Everything the dashboard shows about this player (plan.md §14).
     *
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        return app(CustomerSegmenter::class)->metricsFor($this);
    }

    /**
     * Which segments this customer belongs to.
     *
     * @return Collection<int, CustomerSegment>
     */
    public function segments(): Collection
    {
        return app(CustomerSegmenter::class)->segmentsFor($this);
    }
}

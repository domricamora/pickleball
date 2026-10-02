<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $organization_id
 * @property int|null $branch_id
 * @property bool $is_active
 */
/*
 | Mass-assignable fields. organization_id and branch_id are deliberately
 | omitted: a tenant membership is only ever changed by an Action (tenant
 | onboarding, the installer command), never by request data.
 */
#[Fillable(['name', 'email', 'password', 'phone', 'skill_level'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The tenant this user primarily belongs to. Null for platform staff.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Every tenant this user belongs to, with the role they hold there.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Platform staff operate across every tenant (plan.md §7).
     */
    public function isPlatformStaff(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN->value);
    }

    /**
     * A suspended user must not be able to sign in.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}

<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An employee of a facility (plan.md §19).
 *
 * Separate from User: a staff member may not have a login, and a user may be
 * a customer with no staff record.
 *
 * @property Role $role
 */
#[Fillable([
    'organization_id',
    'user_id',
    'branch_id',
    'employee_number',
    'first_name',
    'last_name',
    'phone',
    'hired_on',
    'role',
    'commission_rate',
    'base_salary',
    'is_active',
])]
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<StaffAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    /**
     * @return HasMany<StaffLeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(StaffLeaveRequest::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'hired_on' => 'date',
            'commission_rate' => 'decimal:2',
            'base_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Commission earned on a peso sales total.
     */
    public function commissionOn(float $salesTotal): float
    {
        return round($salesTotal * ((float) $this->commission_rate / 100), 2);
    }
}

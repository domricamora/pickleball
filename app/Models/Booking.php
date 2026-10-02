<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A reserved slot on a court (plan.md §12).
 *
 * @property BookingStatus $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'court_id',
    'user_id',
    'customer_id',
    'reference',
    'starts_at',
    'ends_at',
    'duration_minutes',
    'status',
    'amount',
    'currency',
    'price_type',
    'players',
    'notes',
    'confirmed_at',
    'checked_in_at',
    'completed_at',
    'cancelled_at',
    'cancelled_by',
    'cancellation_reason',
    'no_show_at',
    'refunded_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<BookingStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'amount' => 'decimal:2',
            'players' => 'integer',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'no_show_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            $booking->reference ??= self::generateReference();
        });
    }

    /**
     * A readable, collision-resistant reference shown to players.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'PB-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (self::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Whether this booking still holds its slot.
     */
    public function occupiesSlot(): bool
    {
        return $this->status->occupiesSlot();
    }

    /**
     * Whether this interval overlaps another. Half-open, so back-to-back
     * bookings do not collide.
     */
    public function overlaps(\DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        return $this->starts_at->lt($end) && $this->ends_at->gt($start);
    }

    /**
     * A Manila-local time range such as "6:30 PM – 8:00 PM".
     */
    public function timeRange(): string
    {
        $tz = config('platform.locale.timezone');

        return $this->starts_at->timezone($tz)->format('g:i A')
            .' – '.$this->ends_at->timezone($tz)->format('g:i A');
    }
}

<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a booking's audit trail (plan.md §12, §30).
 *
 * Deliberately not tenant-scoped: it is reached through its booking and is
 * only ever read alongside it.
 */
#[Fillable(['booking_id', 'user_id', 'from_status', 'to_status', 'note'])]
class BookingStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * The table is singular ("history"), so the inferred plural does not match.
     */
    protected $table = 'booking_status_history';

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
            'created_at' => 'datetime',
        ];
    }
}

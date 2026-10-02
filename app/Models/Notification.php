<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One message to one person (plan.md §21).
 *
 * The row is written synchronously as the record of intent; the queued job
 * performs the delivery and updates the status.
 *
 * @property NotificationType $type
 * @property NotificationChannel $channel
 */
#[Fillable([
    'organization_id',
    'customer_id',
    'user_id',
    'booking_id',
    'event_id',
    'membership_subscription_id',
    'type',
    'channel',
    'subject',
    'body',
    'status',
    'read_at',
    'sent_at',
    'failure_reason',
])]
class Notification extends Model
{
    use BelongsToOrganization, HasUuids;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'channel' => NotificationChannel::class,
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            $notification->id ??= (string) Str::uuid();
            $notification->channel ??= NotificationChannel::IN_APP;
            $notification->status ??= 'queued';
        });
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}

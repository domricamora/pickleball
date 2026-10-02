<?php

namespace App\Enums;

/**
 * Customer segments (plan.md §14).
 *
 * A customer can hold more than one of these at once — a VIP is also a member
 * and a frequent renter — so segments are a set, not a single value.
 */
enum CustomerSegment: string
{
    case NEW_PLAYER = 'new_player';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MEMBER = 'member';
    case VIP = 'vip';
    case TOURNAMENT_PLAYER = 'tournament_player';
    case FREQUENT_RENTER = 'frequent_renter';

    public function label(): string
    {
        return match ($this) {
            self::NEW_PLAYER => 'New player',
            self::ACTIVE => 'Active player',
            self::INACTIVE => 'Inactive player',
            self::MEMBER => 'Member',
            self::VIP => 'VIP',
            self::TOURNAMENT_PLAYER => 'Tournament player',
            self::FREQUENT_RENTER => 'Frequent renter',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::VIP => 'bg-energetic-50 text-energetic-700',
            self::MEMBER, self::TOURNAMENT_PLAYER => 'bg-pickle-50 text-pickle-700',
            self::ACTIVE, self::FREQUENT_RENTER => 'bg-pickle-100 text-pickle-800',
            self::NEW_PLAYER => 'bg-slate-100 text-slate-700',
            self::INACTIVE => 'bg-slate-100 text-slate-600',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

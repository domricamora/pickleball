<?php

namespace App\Enums;

/**
 * How serious an incident report is (plan.md §19).
 */
enum IncidentSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Whether a critical incident should block normal operation.
     */
    public function requiresImmediateAttention(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL], true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CRITICAL => 'bg-red-50 text-red-700',
            self::HIGH => 'bg-energetic-50 text-energetic-700',
            self::MEDIUM => 'bg-slate-100 text-slate-700',
            self::LOW => 'bg-slate-100 text-slate-600',
        };
    }
}

<?php

namespace App\Services\Ai;

use App\Models\Organization;
use RuntimeException;

/**
 * The boundary around generated output (plan.md §22).
 *
 * plan.md is explicit: AI assists operations, it does not control financial or
 * booking decisions. An assistant may read facility data and explain it, but
 * anything that moves money or reserves a court must go through the normal
 * validated path instead. This class is the single place that rule is
 * expressed, so it can be tested rather than trusted to review.
 */
class AiGuard
{
    /**
     * Things an assistant must never do on its own.
     */
    public const FORBIDDEN_ACTIONS = [
        'create_booking',
        'cancel_booking',
        'modify_booking',
        'take_payment',
        'issue_refund',
        'adjust_stock',
        'change_price',
    ];

    /**
     * Refuse an action the assistant is not allowed to take.
     *
     * @throws RuntimeException always, for a forbidden action
     */
    public function assertPermitted(string $action): void
    {
        if (in_array($action, self::FORBIDDEN_ACTIONS, true)) {
            throw new RuntimeException(
                "AI may not '{$action}'. This must go through the normal validated workflow."
            );
        }
    }

    /**
     * Whether an action is safe for an assistant.
     */
    public function isPermitted(string $action): bool
    {
        return ! in_array($action, self::FORBIDDEN_ACTIONS, true);
    }

    /**
     * Every tenant-owned query an assistant may run, keyed by organisation.
     *
     * The assistant is constructed with a single organization and refuses to
     * answer without one, so it cannot be pointed at the whole platform.
     */
    public function assertScope(?Organization $organization): Organization
    {
        if ($organization === null) {
            throw new RuntimeException(
                'An AI assistant must be scoped to a single facility.'
            );
        }

        return $organization;
    }
}

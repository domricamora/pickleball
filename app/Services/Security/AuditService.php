<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * The audit trail (plan.md §24).
 *
 * Two rules:
 *  - the raw IP is never stored, only a keyed hash, so a leaked log cannot
 *    be used to identify a visitor;
 *  - a failure to write the log never breaks the request it was recording.
 *    Losing an audit row is bad, but losing a booking because the audit
 *    table is full is worse.
 */
class AuditService
{
    /**
     * Record an action.
     *
     * @param  array<string, mixed>|null  $changes
     */
    public function record(
        string $action,
        ?object $subject = null,
        ?array $changes = null,
        ?Request $request = null,
    ): ?AuditLog {
        try {
            return AuditLog::create([
                'organization_id' => Auth::user()?->organization_id,
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $subject !== null ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                // A diff, not a full record: a log that copies every column
                // would quietly duplicate personal data.
                'changes' => $changes ? $this->redact($changes) : null,
                'ip_hash' => $request !== null ? $this->hashIp($request->ip()) : null,
                'user_agent' => $request?->userAgent() !== null
                    ? mb_substr($request->userAgent(), 0, 255)
                    : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Hash an IP with a per-install salt, so the log cannot be reversed.
     */
    public function hashIp(string $ip): string
    {
        $salt = (string) config('app.key');

        return hash_hmac('sha256', $ip, $salt);
    }

    /**
     * Drop anything that looks like a secret before it is written.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function redact(array $changes): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];

        $clean = [];

        foreach ($changes as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $clean;
    }
}

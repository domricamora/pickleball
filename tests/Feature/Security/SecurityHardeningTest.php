<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Services\Security\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Phase 16 acceptance checks: audit trail, headers and rate limits
 * (plan.md §24).
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audit = app(AuditService::class);
    }

    public function test_an_action_is_audited(): void
    {
        $log = $this->audit->record('booking.created', Organization::factory()->create());

        $this->assertSame('booking.created', $log->action);
        $this->assertSame('Organization', $log->subject_type);
        $this->assertNotNull($log->subject_id);
    }

    public function test_the_raw_ip_is_never_stored(): void
    {
        $request = Request::create('/book', 'POST', server: ['REMOTE_ADDR' => '203.0.113.42']);

        $log = $this->audit->record('login.attempted', null, null, $request);

        $this->assertNotSame('203.0.113.42', $log->ip_hash);
        $this->assertSame(64, strlen($log->ip_hash), 'A keyed hash, not a readable value.');
    }

    public function test_the_same_ip_hashes_the_same_way(): void
    {
        $a = $this->audit->hashIp('203.0.113.42');
        $b = $this->audit->hashIp('203.0.113.42');

        $this->assertSame($a, $b, 'Hashing must be stable for correlation.');
    }

    public function test_different_ips_hash_differently(): void
    {
        $this->assertNotSame(
            $this->audit->hashIp('203.0.113.42'),
            $this->audit->hashIp('198.51.100.7'),
        );
    }

    public function test_secrets_are_redacted_from_the_log(): void
    {
        $log = $this->audit->record('user.updated', null, [
            'email' => 'player@example.com',
            'password' => 'hunter2',
            'token' => 'abc123',
        ]);

        $this->assertSame('player@example.com', $log->changes['email']);
        $this->assertSame('[redacted]', $log->changes['password']);
        $this->assertSame('[redacted]', $log->changes['token']);
    }

    public function test_nested_secrets_are_redacted(): void
    {
        $log = $this->audit->record('user.updated', null, [
            'meta' => ['api_key' => 'sk_live_x', 'note' => 'fine'],
        ]);

        $this->assertSame('[redacted]', $log->changes['meta']['api_key']);
        $this->assertSame('fine', $log->changes['meta']['note']);
    }

    public function test_an_audit_log_cannot_be_updated(): void
    {
        $log = $this->audit->record('test.action');

        $this->assertFalse($log->update(['action' => 'tampered']));
        $this->assertSame('test.action', $log->fresh()->action, 'A log that can be edited is not a log.');
    }

    public function test_an_audit_log_cannot_be_deleted(): void
    {
        $log = $this->audit->record('test.action');

        $this->assertFalse($log->delete());
        $this->assertSame(1, AuditLog::where('id', $log->id)->count());
    }

    public function test_a_failed_audit_does_not_throw(): void
    {
        // A log that cannot be written must not take the request down with it.
        $log = $this->audit->record(str_repeat('a', 500));

        $this->assertTrue($log === null || $log->exists);
    }

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_the_content_security_policy_blocks_framing(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp, 'Plugins must be blocked.');
        $this->assertStringContainsString("frame-src 'none'", $csp);
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        // Pinning browsers to HTTPS over plain HTTP would break the site.
        $response = $this->get('/');

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_the_booking_limiter_is_tightest(): void
    {
        $limit = RateLimiter::limiter('booking')(
            Request::create('/book', 'POST', server: ['REMOTE_ADDR' => '203.0.113.1'])
        );

        $public = RateLimiter::limiter('public-pages')(
            Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.1'])
        );

        $this->assertSame(10, $limit->maxAttempts);
        $this->assertSame(120, $public->maxAttempts);
    }

    public function test_limiters_are_keyed_per_visitor(): void
    {
        $one = RateLimiter::limiter('booking')(
            Request::create('/book', 'POST', server: ['REMOTE_ADDR' => '203.0.113.1'])
        );
        $two = RateLimiter::limiter('booking')(
            Request::create('/book', 'POST', server: ['REMOTE_ADDR' => '198.51.100.7'])
        );

        $this->assertNotSame($one->key, $two->key, 'One visitor must not exhaust another\'s limit.');
    }
}

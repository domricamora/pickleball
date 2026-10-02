<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 acceptance checks: authentication (plan.md §10).
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/Login')->has('canResetPassword'));
    }

    public function test_register_screen_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/Register'));
    }

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/ForgotPassword'));
    }

    public function test_user_can_register_and_is_given_the_customer_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.test',
            'phone' => '+63 917 000 0000',
            'password' => 'PickleBall2026',
            'password_confirmation' => 'PickleBall2026',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'juan@example.test')->firstOrFail();

        // Self-registration must never grant staff access.
        $this->assertTrue($user->hasRole(Role::CUSTOMER->value));
        $this->assertFalse($user->hasRole(Role::FACILITY_OWNER->value));
        $this->assertFalse($user->hasRole(Role::SUPER_ADMIN->value));
        $this->assertNull($user->organization_id);
        $this->assertNull($user->email_verified_at, 'New accounts must verify their email.');
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $this->post('/register', [
            'name' => 'Weak',
            'email' => 'weak@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'player@example.test']);

        $response = $this->post('/login', [
            'email' => 'player@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(config('fortify.home'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'player@example.test']);

        $this->post('/login', [
            'email' => 'player@example.test',
            'password' => 'not-the-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_throttles_repeated_failures(): void
    {
        User::factory()->create(['email' => 'player@example.test']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'player@example.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        // The limiter allows five attempts a minute; the next is locked out
        // and rejected with 429 even though the password is now correct.
        $this->post('/login', [
            'email' => 'player@example.test',
            'password' => 'password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->inactive()->create(['email' => 'suspended@example.test']);

        $this->post('/login', [
            'email' => 'suspended@example.test',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        User::factory()->create(['email' => 'player@example.test']);

        $this->post('/forgot-password', ['email' => 'player@example.test'])
            ->assertSessionHasNoErrors();
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'player@example.test']);

        $token = app('auth.password.broker')->createToken($user);

        $this->get("/reset-password/{$token}?email=player@example.test")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/ResetPassword')
                ->where('token', $token)
                ->where('email', 'player@example.test'),
            );

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'player@example.test',
            'password' => 'BrandNewPass2026',
            'password_confirmation' => 'BrandNewPass2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(auth()->validate([
            'email' => 'player@example.test',
            'password' => 'BrandNewPass2026',
        ]));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_profile_requires_authentication(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_verified_user_can_reach_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')->has('stats'));
    }

    public function test_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)
            ->put('/profile', [
                'name' => 'New Name',
                'email' => $user->email,
                'phone' => '+63 917 111 1111',
                'skill_level' => 'advanced',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('advanced', $user->skill_level);
    }

    public function test_changing_email_clears_verification(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'changed@example.test',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_super_admin_sees_all_permissions_in_shared_props(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('isPlatformStaff', true)
                ->where('role', Role::SUPER_ADMIN->value)
                ->has('permissions'),
            );
    }

    public function test_customer_does_not_receive_staff_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::CUSTOMER->value);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('isPlatformStaff', false)
                ->where('role', Role::CUSTOMER->value),
            );
    }

    public function test_tenant_user_sees_their_own_organization(): void
    {
        $organization = Organization::factory()->create(['name' => 'Makati Courts']);
        $user = User::factory()->forTenant($organization, Role::MANAGER)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('isPlatformStaff', false)
                ->has('organization')
                ->where('organization.name', 'Makati Courts'),
            );
    }
}

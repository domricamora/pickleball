<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Phase 0 acceptance checks — plan.md §8.
 */
class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_the_marketing_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->where('headline', 'Your Court. Your Game. Your Community.')
                ->has('stats', 3),
        );
    }

    public function test_application_uses_the_manila_timezone(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }

    public function test_database_connection_is_configured(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertNotNull(User::count());
    }
}

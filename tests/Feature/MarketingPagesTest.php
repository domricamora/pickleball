<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 1 acceptance checks for the public marketing site (plan.md §9).
 */
class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function publicPages(): array
    {
        return [
            ['/', 'Home'],
            ['/facilities', 'Facilities'],
            ['/courts', 'Courts'],
            ['/pricing', 'Pricing'],
            ['/memberships', 'Memberships'],
            ['/events', 'Events'],
            ['/tournaments', 'Tournaments'],
            ['/about', 'About'],
            ['/contact', 'Contact'],
            ['/faq', 'Faq'],
            ['/blog', 'Blog'],
        ];
    }

    /**
     * Only the URI is needed for the metadata check.
     *
     * @return array<int, array{0: string}>
     */
    public static function publicUris(): array
    {
        return array_map(fn (array $case): array => [$case[0]], self::publicPages());
    }

    /**
     * Each marketing page must render its own Inertia component.
     */
    #[DataProvider('publicPages')]
    public function test_public_page_renders(string $uri, string $component): void
    {
        $this->get($uri)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component($component));
    }

    /**
     * Head tags must be present in the server response so crawlers and social
     * scrapers see them without executing JavaScript.
     */
    #[DataProvider('publicUris')]
    public function test_public_page_ships_seo_metadata(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertSee('<title>', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:locale" content="en_PH"', false);
        $response->assertSee('name="twitter:card"', false);
    }

    public function test_book_landing_page_renders(): void
    {
        $this->get('/book')
            ->assertOk()
            ->assertSee('Book a Court')
            ->assertSee('Coming soon');
    }

    public function test_sitemap_lists_public_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee(config('app.url').'/pricing', false);
        $response->assertSee(config('app.url').'/faq', false);
    }

    public function test_robots_disallows_private_areas_and_points_at_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /dashboard', false);
        $response->assertSee('Sitemap: '.config('app.url').'/sitemap.xml', false);
    }

    public function test_faq_page_emits_faqpage_structured_data(): void
    {
        $this->get('/faq')->assertSee('"@type":"FAQPage"', false);
    }

    public function test_contact_page_emits_localbusiness_structured_data(): void
    {
        $this->get('/contact')->assertSee('"@type":"LocalBusiness"', false);
    }

    public function test_home_page_emits_organization_and_website_schema(): void
    {
        $response = $this->get('/');

        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"WebSite"', false);
    }

    public function test_shared_props_expose_brand_and_navigation(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->has('brand.name')
                ->has('brand.tagline')
                ->has('brand.headline')
                ->has('nav')
                ->has('coverage')
                ->has('locale.timezone')
                ->where('locale.currency', 'PHP')
                ->where('locale.currencySymbol', '₱')
                ->where('locale.timezone', 'Asia/Manila'),
            );
    }

    public function test_pricing_tiers_are_expressed_in_pesos(): void
    {
        $this->get('/pricing')
            ->assertInertia(fn ($page) => $page
                ->has('tiers', 3)
                ->where('tiers.0.name', 'Drop-in')
                ->where('tiers.0.price', 350)
                ->where('tiers.0.cadence', 'session'),
            );
    }

    public function test_list_pages_do_not_invent_facilities_before_phase_three(): void
    {
        // plan.md §33 forbids fake facilities for SEO, so the list is empty
        // and the pages render an honest empty state instead.
        $this->get('/facilities')
            ->assertInertia(fn ($page) => $page->where('facilities', []));

        $this->get('/events')
            ->assertInertia(fn ($page) => $page->where('events', []));

        $this->get('/tournaments')
            ->assertInertia(fn ($page) => $page->where('tournaments', []));
    }

    public function test_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}

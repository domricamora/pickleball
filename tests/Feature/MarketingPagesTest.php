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
     * The shared basePath must tell the front end where the app is mounted.
     *
     * Links are written root-relative ("/facilities") because that is the route
     * Laravel defines, but a browser resolves a leading slash against the
     * domain root. Served from a subdirectory -- which is how WAMP serves this
     * app, at /p/public -- those links leave the site entirely and land on the
     * server root. The front end prefixes them with this value, so it has to be
     * shared on every response.
     */
    public function test_shared_base_path_is_exposed(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->has('basePath')
                // At the domain root the mount point is genuinely empty, which
                // must stay "" rather than becoming null or "/".
                ->where('basePath', ''),
            );
    }

    /**
     * A subdirectory mount must still route and expose the right base path.
     *
     * Reproduces the WAMP setup -- document root above the app, front controller
     * at /p/public/index.php -- and asserts both that the route resolves and
     * that basePath comes back as the mount point.
     */
    public function test_subdirectory_mount_routes_and_reports_base_path(): void
    {
        // The URI has to be requested at its mounted path, otherwise Laravel's
        // test client resets REQUEST_URI to the domain root and there is no
        // mount point left to detect.
        $response = $this->withServerVariables([
            'SCRIPT_NAME' => '/p/public/index.php',
            'PHP_SELF' => '/p/public/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
        ])->get('/p/public/facilities');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Facilities')
            ->where('basePath', '/p/public'),
        );
    }

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

    public function test_booking_page_renders(): void
    {
        $this->get('/book')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Book')
                ->has('branches')
                ->has('courts')
                ->where('currencySymbol', '₱'),
            );
    }

    /**
     * Every page must ship a React mount node and a module script.
     *
     * The site is client-rendered, so a missing #app div or a missing bundle
     * reference leaves a blank page while every server-side assertion above
     * still passes. This is the check that would have caught the white screen.
     */
    #[DataProvider('publicUris')]
    public function test_public_page_ships_a_mount_node_and_bundle(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertSee('<div id="app">', false);
        $response->assertSee('type="module"', false);
        // The built asset must exist on disk, not merely be referenced.
        $this->assertFileExists(public_path('build/manifest.json'));
    }

    /**
     * A page prop must never shadow a shared prop.
     *
     * The Contact page once returned a `contact` prop that replaced the shared
     * `contact` object, leaving the footer with no phone number and throwing
     * in the browser while the server response stayed perfectly valid.
     */
    public function test_page_props_do_not_shadow_shared_contact(): void
    {
        $response = $this->get('/contact');

        $response->assertInertia(fn ($page) => $page
            ->component('Contact')
            // The shared contact the footer depends on stays intact...
            ->has('contact.email')
            ->has('contact.phone')
            // ...and the page's own copy lives under its own name.
            ->has('contactPage.title')
            ->has('contactPage.description'),
        );
    }

    /**
     * Inertia v3 keeps the current URL at the top level of the page object.
     *
     * Components reading it from props got undefined and threw on first
     * render, which blanked every page on the site.
     */
    public function test_inertia_payload_exposes_url_at_the_top_level(): void
    {
        $content = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression('/"url":"[^"]*"/', $content);
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

    /**
     * The redesigned home page is assembled from these config-driven blocks.
     *
     * Each one drives a distinct section, so a missing prop silently deletes a
     * whole band of the page rather than throwing — which is exactly the class
     * of regression this catches.
     */
    public function test_home_page_ships_every_designed_section(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('hero.eyebrow')
                ->has('heroStats', 4)
                ->has('courtsSection.title')
                ->has('rentalsSection.title')
                ->has('services', 3)
                ->has('equipment.items')
                ->has('experienceEvents.title')
                ->has('experiencePillars', 3)
                ->has('bookingSteps', 3),
            );
    }

    /**
     * Equipment prices are published as peso strings, and the panel is a table.
     *
     * The equipment list is the one place the marketing site states a specific
     * rate, so it is worth asserting the currency marker survives.
     */
    public function test_home_page_equipment_prices_are_in_pesos(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->has('equipment.items')
                ->where('equipment.items.0.0', 'Premium paddles')
                ->where('equipment.items.0.1', '₱80 / session'),
            );
    }

    /**
     * Every photograph the site references must exist on disk.
     *
     * A missing file is not a server error — the layout still renders and every
     * Inertia assertion above still passes, so only a direct file check catches
     * the broken images.
     *
     * @return array<int, array{0: string}>
     */
    public static function mediaFiles(): array
    {
        return array_map(fn (string $file): array => [$file], [
            'courts-hall.webp',
            'courts-detail.webp',
            'courts-row.webp',
            'courts-aerial.webp',
            'courts-outdoor.webp',
            'courts-gear.webp',
            'courts-sky.webp',
            'courts-stacking.webp',
        ]);
    }

    #[DataProvider('mediaFiles')]
    public function test_credited_media_exists(string $file): void
    {
        $this->assertFileExists(public_path("media/{$file}"));
    }

    /**
     * A shipped photograph must contain an actual photograph.
     *
     * These crops are produced by storage/app/build-media.php, and a broken
     * crop is silent: the file exists, is credited, passes every file-existence
     * check, and simply renders as a flat block of colour. GD in particular
     * mis-decoded several of the source JPEGs into solid blue at 6 KB. A real
     * photograph of courts compresses far larger, so a floor catches the
     * failure at the point it would otherwise ship.
     */
    #[DataProvider('mediaFiles')]
    public function test_media_is_not_a_flat_crop(string $file): void
    {
        $this->assertGreaterThan(
            20 * 1024,
            filesize(public_path("media/{$file}")),
            "{$file} is suspiciously small -- it may be a flat or blank crop rather than a photograph.",
        );
    }

    /**
     * The hero loop and its poster must ship, and the video must be real
     * footage rather than a stub.
     *
     * The poster is what reduced-motion users and autoplay-blocked browsers
     * see, so the hero depends on both files: without the video it is a static
     * photo, and without the poster it is a black box.
     */
    public function test_hero_video_and_poster_ship(): void
    {
        $this->assertFileExists(public_path('media/hero-loop.mp4'));
        $this->assertFileExists(public_path('media/hero-poster.webp'));

        // A header-only or truncated MP4 would autoplay as a black frame.
        $this->assertGreaterThan(
            512 * 1024,
            filesize(public_path('media/hero-loop.mp4')),
            'hero-loop.mp4 is too small to be real footage.',
        );

        // "ftyp" at the start of the file is what distinguishes a real MP4.
        $head = (string) file_get_contents(public_path('media/hero-loop.mp4'), false, null, 0, 16);
        $this->assertStringContainsString(
            'ftyp',
            $head,
            'hero-loop.mp4 does not start with an MP4 file-type box.',
        );
    }

    /**
     * The hero must reference a poster and a video, with autoplay attributes
     * that browsers will actually honour.
     *
     * This is a source assertion rather than a response one: this is a
     * client-rendered Inertia app, so the initial HTML contains only the
     * page props and an empty #app. A missing hero video therefore still
     * returns 200 and passes every response assertion in this file while the
     * visitor sees a bare background -- which is exactly what happened.
     */
    public function test_home_hero_ships_a_poster_and_video(): void
    {
        $hero = (string) file_get_contents(resource_path('js/components/marketing/Hero.tsx'));

        $this->assertStringContainsString('hero-poster.webp', $hero, 'The hero has no poster image.');
        $this->assertStringContainsString('hero-loop.mp4', $hero, 'The hero has no background video.');
        $this->assertStringContainsString('playsInline', $hero);
        $this->assertStringContainsString('autoPlay', $hero);
        $this->assertStringContainsString('muted', $hero);

        // Both must be routed through assetUrl or they 404 on a subdirectory mount.
        $this->assertMatchesRegularExpression(
            '/assetUrl\(.+hero-loop\.mp4.\)/',
            $hero,
            'The hero video is not prefixed with the mount point.',
        );
    }

    /**
     * Media URLs must be prefixed with the mount point.
     *
     * A root-relative "/media/courts.webp" resolves against the domain root, so
     * under a subdirectory mount the browser asks the server root for it and
     * gets a 404 -- invisible to Inertia assertions, and the entire reason the
     * marketing pages rendered with no photography. assetUrl() exists in
     * resources/js/lib/format.ts to prevent it, and this asserts it is applied
     * to every media reference in the components.
     */
    public function test_media_paths_go_through_asset_url(): void
    {
        $components = $this->marketingComponents();

        $this->assertNotEmpty($components, 'No marketing components were discovered to check.');

        foreach ($components as $file) {
            $contents = (string) file_get_contents($file);
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

            // A literal src="/media/..." cannot resolve under a subdirectory mount.
            $this->assertDoesNotMatchRegularExpression(
                '/src\s*=\s*"\/media\//',
                $contents,
                "{$relative} hard-codes a root-relative media path; use assetUrl() instead.",
            );
        }
    }

    /**
     * Every marketing component that renders an <img> for media must import
     * assetUrl, so the mount prefix is actually applied.
     *
     * @return array<int, string>
     */
    protected function marketingComponents(): array
    {
        $files = glob(resource_path('js/pages/*.tsx')) ?: [];
        $files = array_merge($files, glob(resource_path('js/components/marketing/*.tsx')) ?: []);
        $files = array_merge($files, [resource_path('js/layouts/PageLayout.tsx')]);

        return array_values(array_filter($files, function (string $file): bool {
            return str_contains((string) file_get_contents($file), '/media/');
        }));
    }

    /**
     * Every file in public/media must be recorded in the credits file.
     *
     * plan.md §5 and the media rules require a source, author and licence for
     * every shipped asset. An uncredited file is a licence bug, so the two
     * directories are checked against each other in both directions.
     */
    public function test_media_and_credits_stay_in_sync(): void
    {
        $mediaDir = public_path('media');

        if (! is_dir($mediaDir)) {
            $this->fail('public/media is missing; the marketing site has no photography.');
        }

        $credits = (string) file_get_contents(resource_path('media/media-credits.md'));
        $shipped = [];

        foreach (scandir($mediaDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $shipped[] = $entry;
        }

        $this->assertNotEmpty($shipped, 'No media files were produced.');

        foreach ($shipped as $file) {
            $this->assertStringContainsString(
                $file,
                $credits,
                "{$file} ships but has no entry in resources/media/media-credits.md",
            );
        }
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

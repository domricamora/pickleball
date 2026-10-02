<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * SEO endpoints (plan.md §9): XML sitemap and robots.txt.
 */
class SeoController extends Controller
{
    /**
     * Public marketing URLs. Tenant facility and location pages are added once
     * real facilities exist — plan.md §33 forbids inventing them for SEO.
     *
     * @var array<int, array{loc: string, priority: string, changefreq: string}>
     */
    private const STATIC_ROUTES = [
        ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => '/facilities', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => '/courts', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => '/book', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => '/pricing', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['loc' => '/memberships', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['loc' => '/events', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['loc' => '/tournaments', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['loc' => '/blog', 'priority' => '0.6', 'changefreq' => 'weekly'],
        ['loc' => '/about', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => '/login', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['loc' => '/register', 'priority' => '0.4', 'changefreq' => 'yearly'],
    ];

    /**
     * Routes that must never be indexed (customer or staff areas).
     *
     * @var array<int, string>
     */
    private const DISALLOWED = [
        '/dashboard',
        '/admin',
        '/profile',
    ];

    public function sitemap(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $urls = implode("\n", array_map(function (array $route) use ($base): string {
            return <<<XML
                <url>
                    <loc>{$base}{$route['loc']}</loc>
                    <changefreq>{$route['changefreq']}</changefreq>
                    <priority>{$route['priority']}</priority>
                </url>
            XML;
        }, self::STATIC_ROUTES));

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            {$urls}
            </urlset>
            XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $disallow = implode("\n", array_map(
            fn (string $path): string => "Disallow: {$path}",
            self::DISALLOWED,
        ));

        $body = <<<TXT
            User-agent: *
            {$disallow}

            Sitemap: {$base}/sitemap.xml
            TXT;

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}

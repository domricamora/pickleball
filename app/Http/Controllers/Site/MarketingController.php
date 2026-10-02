<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public marketing pages (plan.md §4, §9).
 *
 * Copy and pricing come from config/marketing.php. Facility, court, event and
 * tournament records arrive with their respective phases; until then these
 * pages render honest empty states rather than invented data — plan.md §33
 * forbids fake facilities for SEO.
 */
class MarketingController extends Controller
{
    /**
     * Metadata rendered server-side in the root view (see app.blade.php) so
     * crawlers and social scrapers see it without executing JavaScript.
     * The React <Seo> component renders the same values after hydration.
     *
     * @param  array<int, array<string, mixed>>  $schema
     * @return array<string, mixed>
     */
    protected function seo(string $title, string $description, array $schema = [], string $type = 'website'): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'schema' => $schema,
        ];
    }

    public function home(): Response
    {
        $brand = config('platform.name');
        $description = config('platform.description');

        return Inertia::render('Home', [
            'seo' => $this->seo(
                "{$brand} — ".config('platform.headline'),
                $description,
                [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Organization',
                        'name' => $brand,
                        'description' => $description,
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebSite',
                        'name' => $brand,
                    ],
                ],
            ),
            'hero' => config('marketing.hero'),
            'heroStats' => config('marketing.hero_stats'),
            'courtsSection' => config('marketing.courts_section'),
            'rentalsSection' => config('marketing.rentals_section'),
            'services' => config('marketing.services'),
            'equipment' => config('marketing.equipment'),
            'experienceEvents' => config('marketing.experience_events'),
            'experiencePillars' => config('marketing.experience_pillars'),
            'bookingSteps' => config('marketing.booking_steps'),
            'features' => config('marketing.features'),
            'operatorFeatures' => config('marketing.operator_features'),
            'operatorCta' => config('marketing.operator_cta'),
            'community' => config('marketing.community'),
        ]);
    }

    public function facilities(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Facilities', [
            'seo' => $this->seo(
                "Facilities — {$brand}",
                'Browse pickleball facilities across the Philippines. '.config('platform.description'),
            ),
            'filters' => config('marketing.find_your_game.filters'),
            'coverage' => config('platform.coverage'),
            'facilities' => [],
        ]);
    }

    public function courts(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Courts', [
            'seo' => $this->seo(
                "Courts — {$brand}",
                'Indoor and outdoor pickleball courts across the Philippines, bookable by the hour.',
            ),
            'facilities' => [],
        ]);
    }

    public function pricing(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Pricing', [
            'seo' => $this->seo(
                "Pricing — {$brand}",
                'Transparent pickleball pricing in Philippine pesos. Drop-in sessions, monthly and annual memberships.',
            ),
            'tiers' => config('marketing.pricing'),
        ]);
    }

    public function memberships(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Memberships', [
            'seo' => $this->seo(
                "Memberships — {$brand}",
                'Pickleball memberships and session packages with member rates, priority booking and guest passes.',
            ),
            'content' => config('marketing.memberships'),
            'packages' => config('marketing.packages'),
            'tiers' => config('marketing.pricing'),
        ]);
    }

    public function events(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Events', [
            'seo' => $this->seo(
                "Events — {$brand}",
                'Open play, beginner sessions, clinics, leagues and community pickleball events across the Philippines.',
            ),
            'events' => [],
        ]);
    }

    public function tournaments(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Tournaments', [
            'seo' => $this->seo(
                "Tournaments — {$brand}",
                'Pickleball tournaments and leagues in the Philippines with divisions, brackets, schedules and results.',
            ),
            'tournaments' => [],
        ]);
    }

    public function about(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('About', [
            'seo' => $this->seo("About — {$brand}", config('platform.description')),
            'about' => config('marketing.about'),
        ]);
    }

    public function contact(): Response
    {
        $brand = config('platform.name');
        $address = config('platform.contact.address');

        return Inertia::render('Contact', [
            'seo' => $this->seo(
                "Contact — {$brand}",
                config('marketing.contact.description'),
                [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'LocalBusiness',
                        'name' => $brand,
                        'description' => config('platform.description'),
                        'email' => config('platform.contact.email'),
                        'telephone' => config('platform.contact.phone'),
                        'address' => [
                            '@type' => 'PostalAddress',
                            'streetAddress' => $address['street'],
                            'addressLocality' => $address['city'],
                            'addressRegion' => $address['province'],
                            'postalCode' => $address['postal_code'],
                            'addressCountry' => $address['country'],
                        ],
                    ],
                ],
            ),
            // Named contactPage, not contact: a page prop called "contact"
            // would shadow the shared `contact` object that the footer reads,
            // leaving the footer without a phone number.
            'contactPage' => config('marketing.contact'),
            'details' => [
                'email' => config('platform.contact.email'),
                'phone' => config('platform.contact.phone'),
                'address' => $address,
            ],
        ]);
    }

    public function faq(): Response
    {
        $brand = config('platform.name');
        $items = config('marketing.faq');

        return Inertia::render('Faq', [
            'seo' => $this->seo(
                "FAQ — {$brand}",
                'Answers about booking pickleball courts, payments in Philippine pesos, memberships and coaching.',
                [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => array_map(fn (array $item) => [
                            '@type' => 'Question',
                            'name' => $item[0],
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
                        ], $items),
                    ],
                ],
            ),
            'items' => $items,
        ]);
    }

    public function blog(): Response
    {
        $brand = config('platform.name');

        return Inertia::render('Blog', [
            'seo' => $this->seo(
                "Blog — {$brand}",
                'Pickleball news, guides and tips for players in the Philippines.',
            ),
            'posts' => [],
        ]);
    }
}

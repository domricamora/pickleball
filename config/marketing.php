<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketing site content
    |--------------------------------------------------------------------------
    | Copy for the public marketing site (plan.md §4). Kept in config rather
    | than in components so copy can be edited, translated or moved into the
    | database later without touching the views.
    */

    'hero' => [
        'eyebrow' => 'Premium pickleball facility',
    ],

    /*
     * Hero stat strip.
     *
     * Every value here is derived from configuration or a hard platform fact —
     * never from invented usage numbers. plan.md §33 forbids fabricated figures
     * on the marketing site, so there is deliberately no "players", "rating" or
     * "members" count: those cannot be substantiated before facilities are
     * live, and a made-up rating would be the single most damaging lie on the
     * page.
     */
    'hero_stats' => [
        ['value' => '3', 'label' => 'Steps to book'],
        ['value' => '24/7', 'label' => 'Online booking'],
        ['value' => 'GCash · Maya', 'label' => 'Payment options'],
        ['value' => '₱', 'label' => 'Peso pricing'],
    ],

    /*
     * "The courts" split section.
     */
    'courts_section' => [
        'eyebrow' => 'The courts',
        'title' => 'Play your way.',
        'description' => 'Indoor and outdoor courts built for casual games, serious matches and everything in between. Book the court, pick the hour, and it is yours.',
        'cta' => 'Explore courts',
    ],

    /*
     * "Court rentals" split section.
     */
    'rentals_section' => [
        'eyebrow' => 'Court rentals',
        'title' => 'Your court. Your time.',
        'description' => 'Book by the hour and play at your own pace. Whether you are getting together with friends or running a competitive match, you get the court and the time you need.',
        'cta' => 'Book a court',
    ],

    /*
     * The three "how to play" service cards.
     */
    'services' => [
        [
            'name' => 'Open play',
            'price' => 'From ₱350 / session',
            'description' => 'Casual games with other players at your level.',
            'cta' => 'Book now',
        ],
        [
            'name' => 'Court rental',
            'price' => 'From ₱350 / hour',
            'description' => 'A private court for your group, your way.',
            'cta' => 'Book court',
        ],
        [
            'name' => 'Group / event',
            'price' => 'Custom pricing',
            'description' => 'Parties, leagues, corporate events and tournaments.',
            'cta' => 'Plan an event',
        ],
    ],

    /*
     * Equipment rentals price panel.
     *
     * Indicative platform rates; each facility publishes its own prices.
     */
    'equipment' => [
        'title' => 'Equipment rentals',
        'description' => 'High-quality paddles, balls and more — available to rent so you can play without the hassle.',
        'cta' => 'View equipment',
        'items' => [
            ['Premium paddles', '₱80 / session'],
            ['Standard paddles', '₱50 / session'],
            ['Balls', '₱20 / session'],
            ['Court equipment', '₱50 / session'],
            ['Lockers', '₱50 / session'],
            ['Towels', '₱20 / session'],
            ['Demo paddles', '₱100 / session'],
        ],
    ],

    /*
     * Experience events band.
     */
    'experience_events' => [
        'eyebrow' => 'Experience events',
        'title' => 'More than a court.',
        'description' => 'Meet people, play longer, stay for another game.',
    ],

    'experience_pillars' => [
        ['Play', 'Courts designed for great games.'],
        ['Connect', 'A community of players at every level.'],
        ['Compete', 'Leagues, tournaments and events.'],
    ],

    /*
     * Community section (plan.md §33). Framed as capabilities rather than
     * member counts, because no membership totals can be claimed yet.
     */

    /*
    | "Find Your Game" — the dimensions the search UI supports. Facilities are
    | supplied by tenants from Phase 3 onward.
    */

    'find_your_game' => [
        'title' => 'Find Your Game',
        'description' => 'Search by city, court type and availability to find a court that fits your next match.',
        'filters' => [
            'location' => 'Location',
            'court_type' => 'Court type',
            'surface' => 'Surface',
            'time' => 'Time of day',
        ],
    ],

    /*
    | "Book in Seconds" — the three-step flow (plan.md §4, §36).
    */

    'booking_steps' => [
        [
            'title' => 'Choose facility',
            'description' => 'Pick the court closest to you and check live availability.',
        ],
        [
            'title' => 'Pick date and time',
            'description' => 'Choose an open slot that fits your schedule.',
        ],
        [
            'title' => 'Pay and confirm',
            'description' => 'Pay with GCash, Maya, a card or cash and get an instant confirmation.',
        ],
    ],

    /*
    | "More Than Court Rental" feature grid (plan.md §4).
    */

    'features' => [
        ['Court bookings', 'Reserve any court by the hour with real-time availability.'],
        ['Open play', 'Join scheduled open-play sessions and meet other players at your level.'],
        ['Tournaments', 'Enter local leagues and tournaments with divisions and brackets.'],
        [
            'Coaching',
            'Book lessons and clinics, whether you are picking up a paddle for the first time or sharpening your game.',
        ],
        ['Memberships', 'Save with monthly plans, session packages and member-only rates.'],
        ['Equipment', 'Buy paddles, balls and grips, or rent gear when you play.'],
        ['Events', 'Community events, beginner sessions and social games.'],
    ],

    /*
    | "Built for Court Owners" operator capabilities (plan.md §4).
    */

    'operator_features' => [
        ['Booking management', 'Accept, reschedule and cancel bookings with no double-booking.'],
        ['Customer management', 'Know your players, their history and their preferences.'],
        ['POS', 'Sell products and take payments from one register.'],
        ['Inventory', 'Track stock, suppliers, reorder levels and movements.'],
        ['Staff', 'Schedules, roles and attendance for your team.'],
        ['Reports', 'Revenue, occupancy and utilization by court, branch and day.'],
        ['Promotions', 'Memberships, packages, promos and discounts.'],
        ['Analytics', 'See which courts and hours actually earn their keep.'],
    ],

    'operator_cta' => [
        'title' => 'Run Your Pickleball Facility Smarter',
        'description' => 'One platform for bookings, customers, payments, inventory and reporting.',
        'button' => 'Run Your Pickleball Facility Smarter',
    ],

    /*
    | Community section (plan.md §4).
    */

    'community' => [
        'title' => 'More than a place to play',
        'description' => 'Pickleball grows fastest when players find each other. Join local leagues, beginner sessions and social games at your level.',
        'items' => ['Local players', 'Leagues', 'Events', 'Tournaments', 'Beginner sessions', 'Social games'],
    ],

    /*
    | Pricing (plan.md §9). Amounts are pesos; tax treatment is administrative
    | and applied by each facility (plan.md §32).
    */

    'pricing' => [
        [
            'name' => 'Drop-in',
            'price' => 350,
            'cadence' => 'session',
            'description' => 'Perfect for your first game or an occasional visit.',
            'features' => ['One court booking', 'Standard public rate', 'Pay on arrival', 'No commitment'],
            'highlighted' => false,
            'badge' => null,
        ],
        [
            'name' => 'Player',
            'price' => 1800,
            'cadence' => 'month',
            'description' => 'For regulars who play most weeks.',
            'features' => [
                'Monthly court hours',
                'Member booking rates',
                'Priority booking window',
                'Equipment discounts',
                'Free hours in your birthday month',
            ],
            'highlighted' => true,
            'badge' => 'Most popular',
        ],
        [
            'name' => 'Annual',
            'price' => 18000,
            'cadence' => 'year',
            'description' => 'The best value for a full year of play.',
            'features' => [
                'Everything in Player',
                'Two months free',
                'Guest passes each month',
                'Members-only event access',
                'Lock in your rate',
            ],
            'highlighted' => false,
            'badge' => null,
        ],
    ],

    /*
    | Membership detail page (plan.md §9 and roadmap §15).
    */

    'memberships' => [
        'title' => 'Memberships built around how often you play',
        'description' => 'Play once a week or three times a day. Plans, packages and benefits scale with you, and everything is billed in Philippine pesos.',
        'benefits' => [
            ['Discount', 'Member pricing on every court hour.'],
            ['Priority booking', 'Book earlier than non-members.'],
            ['Free sessions', 'Included hours each period.'],
            ['Member-only events', 'First access to leagues and clinics.'],
            ['Guest passes', 'Bring a friend at a reduced rate.'],
        ],
    ],

    /*
    | Packages (plan.md §15). Each package tracks its own usage and expiry.
    */

    'packages' => [
        ['5 sessions', 'A flexible starter pack with a full month to use it.'],
        ['10 sessions', 'Better value for players on a weekly rhythm.'],
        ['Off-peak package', 'Cheaper hours for daytime and weekday play.'],
    ],

    /*
    | FAQ (plan.md §9).
    */

    'faq' => [
        [
            'Do I need to be an experienced player?',
            'Not at all. Beginners are welcome at every facility. Start with a drop-in session or a beginner clinic, and borrow equipment if you do not own a paddle yet.',
        ],
        [
            'How do I book a court?',
            'Choose a facility, pick a date and an open time slot, then pay. You will get an instant confirmation with the court details. Most bookings take under a minute.',
        ],
        [
            'What payment methods can I use?',
            'You can pay online with GCash, Maya or a debit or credit card through our payment partner, or pay cash at the facility. Prices are always shown in Philippine pesos.',
        ],
        [
            'Can I cancel or reschedule a booking?',
            'Yes. You can reschedule or cancel from your dashboard. Refunds follow the facility policy shown at the time of booking.',
        ],
        [
            'Is a membership required?',
            'No. Membership is optional. Drop-in play is always available; members simply get lower rates, earlier booking and included sessions.',
        ],
        [
            'Do you offer coaching for beginners?',
            'Yes. Facilities host beginner sessions and clinics, and you can book coaching directly. Ask the front desk at your court.',
        ],
    ],

    /*
    | About page (plan.md §9).
    */

    'about' => [
        'mission_title' => 'Why we built this',
        'mission_body' => 'Playing pickleball in the Philippines should be as easy as booking a table. Court times were scattered across text messages and paper notebooks, pricing was unclear, and finding a game meant asking around. PicklePlay brings courts, coaches, leagues and equipment into one place, so finding and booking a court takes seconds instead of a whole afternoon.',
        'values' => [
            ['Play', 'Make it easy for a complete beginner to get on a court for the first time.'],
            ['Book', 'Fast, reliable booking that never double-books a court.'],
            ['Compete', 'Leagues and tournaments for players who want a challenge.'],
            ['Connect', 'A local community, not just a place to hit a ball.'],
        ],
    ],

    /*
    | Contact page (plan.md §9).
    */

    'contact' => [
        'title' => 'Get in touch',
        'description' => 'Questions about booking, membership or bringing your facility onto the platform? We are happy to help.',
    ],

];

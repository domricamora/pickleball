<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    | Brand values live here rather than in components so the platform can be
    | white-labelled per tenant later. See plan.md §3 and §6.
    */

    'name' => env('APP_NAME', 'PicklePlay'),

    'tagline' => 'Play. Book. Compete. Connect.',

    'headline' => 'Your Court. Your Game. Your Community.',

    'description' => env(
        'APP_DESCRIPTION',
        'Book pickleball courts, join games, manage memberships, buy products and stay connected '
            .'with your local pickleball community.',
    ),

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    | Philippine defaults: peso, Asia/Manila, PH mobile numbers.
    */

    'locale' => [
        'country' => 'PH',
        'language' => 'en',
        'currency' => 'PHP',
        'currency_symbol' => '₱',
        'timezone' => 'Asia/Manila',
        'phone_e164' => '+63',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    | Placeholder contact details for the platform itself. A tenant facility
    | supplies its own; these are only used on platform-wide pages.
    */

    'contact' => [
        'email' => env('PLATFORM_CONTACT_EMAIL', 'hello@pickleplay.ph'),
        'phone' => env('PLATFORM_CONTACT_PHONE', '+63 2 8123 4567'),
        'address' => [
            'street' => env('PLATFORM_CONTACT_STREET', '123 Sports Boulevard'),
            'barangay' => env('PLATFORM_CONTACT_BARANGAY', 'San Lorenzo'),
            'city' => env('PLATFORM_CONTACT_CITY', 'Makati City'),
            'province' => env('PLATFORM_CONTACT_PROVINCE', 'Metro Manila'),
            'region' => env('PLATFORM_CONTACT_REGION', 'NCR'),
            'postal_code' => env('PLATFORM_CONTACT_POSTAL', '1200'),
            'country' => 'PH',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social profiles
    |--------------------------------------------------------------------------
    */

    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK', 'https://facebook.com/pickleplay.ph'),
        'instagram' => env('SOCIAL_INSTAGRAM', 'https://instagram.com/pickleplay.ph'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    'nav' => [
        ['label' => 'Facilities', 'href' => '/facilities'],
        ['label' => 'Courts', 'href' => '/courts'],
        ['label' => 'Pricing', 'href' => '/pricing'],
        ['label' => 'Memberships', 'href' => '/memberships'],
        ['label' => 'Events', 'href' => '/events'],
        ['label' => 'Tournaments', 'href' => '/tournaments'],
        ['label' => 'About', 'href' => '/about'],
        ['label' => 'Contact', 'href' => '/contact'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locations served
    |--------------------------------------------------------------------------
    | Used for the footer and SEO. These are coverage areas, not facilities:
    | plan.md §33 forbids inventing fake facility pages for SEO.
    */

    'coverage' => [
        'Metro Manila',
        'Cebu',
        'Davao',
        'Iloilo',
        'Baguio',
        'Cagayan de Oro',
    ],

];

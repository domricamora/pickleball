<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        {{--
            Server-rendered SEO tags.

            React's <Seo> component keeps these in sync after hydration, but
            crawlers and social-card scrapers that do not execute JavaScript
            only ever see this markup — so it must be correct on its own.
        --}}
        @php
            $seo = $page['props']['seo'] ?? [];
            $seoTitle = $seo['title'] ?? config('platform.name');
            $seoDescription = $seo['description'] ?? config('platform.description');
            $seoImage = $seo['image'] ?? null;
            $seoType = $seo['type'] ?? 'website';
            $seoSchema = $seo['schema'] ?? [];
            $seoSchema = array_is_list($seoSchema) ? $seoSchema : ($seoSchema ? [$seoSchema] : []);
        @endphp

        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}" />
        <link rel="canonical" href="{{ url()->current() }}" />

        <meta property="og:site_name" content="{{ config('platform.name') }}" />
        <meta property="og:title" content="{{ $seoTitle }}" />
        <meta property="og:description" content="{{ $seoDescription }}" />
        <meta property="og:type" content="{{ $seoType }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:locale" content="en_PH" />
        @if ($seoImage)
            <meta property="og:image" content="{{ $seoImage }}" />
        @endif

        <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}" />
        <meta name="twitter:title" content="{{ $seoTitle }}" />
        <meta name="twitter:description" content="{{ $seoDescription }}" />
        @if ($seoImage)
            <meta name="twitter:image" content="{{ $seoImage }}" />
        @endif

        @foreach ($seoSchema as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endforeach

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-soft-bg text-ink antialiased">
        @inertia
    </body>
</html>
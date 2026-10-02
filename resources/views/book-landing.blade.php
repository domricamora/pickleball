<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex, follow" />

        <title>{{ config('platform.name') }} — Book a Court</title>
        <meta name="description" content="{{ config('platform.description') }}" />
        <link rel="canonical" href="{{ url('/book') }}" />

        @fonts

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-soft-bg text-ink">
        <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col justify-center px-6 py-16">
            <span class="bg-pickle-50 text-pickle-700 w-fit rounded-full px-4 py-1.5 text-xs font-bold tracking-wide uppercase">
                Coming soon
            </span>

            <h1 class="font-display mt-6 text-4xl font-extrabold text-pickle-900 sm:text-5xl">
                Book a Court
            </h1>

            <p class="text-slate mt-5 text-lg">
                The booking engine arrives in Phase 4. The flow it will follow is short by design:
            </p>

            <ol class="mt-8 space-y-3">
                @foreach (['Facility', 'Court', 'Time', 'Customer', 'Payment', 'Confirmation'] as $index => $step)
                    <li class="border-hairline flex items-center gap-4 rounded-card border bg-white px-5 py-4">
                        <span class="bg-pickle-500 grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-bold text-white">
                            {{ $index + 1 }}
                        </span>
                        <span class="font-semibold text-pickle-900">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="mt-10 flex flex-wrap gap-3">
                <a
                    href="{{ url('/facilities') }}"
                    class="bg-energetic-500 hover:bg-energetic-600 rounded-card px-7 py-3.5 font-semibold text-white transition-colors"
                >
                    Explore Facilities
                </a>
                <a
                    href="{{ url('/') }}"
                    class="border-hairline text-pickle-800 hover:bg-pickle-50 rounded-card border bg-white px-7 py-3.5 font-semibold transition-colors"
                >
                    Back to home
                </a>
            </div>
        </main>
    </body>
</html>
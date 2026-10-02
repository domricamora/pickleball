<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay for {{ $booking->reference }} — {{ config('platform.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body class="bg-soft-bg h-full text-slate-900">
    <main class="mx-auto max-w-2xl px-6 py-16">
        <h1 class="font-display text-pickle-900 text-3xl font-extrabold">Choose how to pay</h1>
        <p class="text-slate mt-2 text-sm">Booking {{ $booking->reference }} · {{ $amount }}</p>

        @if (session('success'))
            <p class="border-hairline text-pickle-800 mt-6 rounded-card border bg-pickle-50 px-4 py-3 text-sm">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('payments.checkout', $booking) }}" class="mt-8 space-y-3">
            @csrf
            @foreach ($methods as $method)
                <button
                    type="submit"
                    name="method"
                    value="{{ $method->value }}"
                    class="border-hairline bg-white hover:bg-pickle-50 w-full rounded-card border px-5 py-4 text-left text-sm font-semibold transition-colors"
                >
                    {{ $method->label() }}
                    <span class="text-slate block text-xs font-normal">
                        {{ $method->isGatewayBacked() ? 'Paid securely through PayMongo.' : 'Settled at the counter.' }}
                    </span>
                </button>
            @endforeach
        </form>
    </main>
</body>
</html>
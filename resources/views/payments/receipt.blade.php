<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->reference }} — {{ config('platform.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body class="bg-soft-bg h-full text-slate-900">
    <main class="mx-auto max-w-2xl px-6 py-16">
        <h1 class="font-display text-pickle-900 text-3xl font-extrabold">Payment receipt</h1>

        <dl class="border-hairline bg-white mt-8 divide-y divide-hairline rounded-panel border">
            <div class="flex justify-between px-6 py-4">
                <dt class="text-sm">Reference</dt>
                <dd class="text-sm font-semibold">{{ $payment->reference }}</dd>
            </div>
            <div class="flex justify-between px-6 py-4">
                <dt class="text-sm">Method</dt>
                <dd class="text-sm font-semibold">{{ $payment->methodLabel() }}</dd>
            </div>
            <div class="flex justify-between px-6 py-4">
                <dt class="text-sm">Status</dt>
                <dd class="text-sm font-semibold">{{ $payment->status->label() }}</dd>
            </div>
            <div class="flex justify-between px-6 py-4">
                <dt class="text-sm">Amount</dt>
                <dd class="text-base font-bold">{{ $amount }}</dd>
            </div>
        </dl>
    </main>
</body>
</html>
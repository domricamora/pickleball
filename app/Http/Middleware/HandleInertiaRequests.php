<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default with every Inertia response.
     *
     * Brand and navigation live in config/platform.php so no component
     * hard-codes them (plan.md §3, §6).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'brand' => [
                'name' => config('platform.name'),
                'tagline' => config('platform.tagline'),
                'headline' => config('platform.headline'),
                'description' => config('platform.description'),
            ],

            'nav' => config('platform.nav'),
            'coverage' => config('platform.coverage'),
            'social' => config('platform.social'),

            'locale' => [
                'currency' => config('platform.locale.currency'),
                'currencySymbol' => config('platform.locale.currency_symbol'),
                'timezone' => config('platform.locale.timezone'),
            ],

            'contact' => [
                'email' => config('platform.contact.email'),
                'phone' => config('platform.contact.phone'),
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],

            'auth' => [
                'user' => fn () => $request->user()?->only([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'email_verified_at',
                    'is_active',
                ]),
                'role' => fn () => $request->user()?->getRoleNames()->first(),
                'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name')->values(),
                'isPlatformStaff' => fn () => (bool) $request->user()?->isPlatformStaff(),
                'organizationId' => fn () => $request->user()?->organization_id,
                'branchId' => fn () => $request->user()?->branch_id,
            ],

            'permissions' => function () use ($request): array {
                if (! $request->user()) {
                    return [];
                }

                $user = $request->user();

                // Super Admin holds every permission name so the front end can
                // hide nothing on their behalf by accident.
                if ($user->isPlatformStaff()) {
                    return Permission::query()->pluck('name')->all();
                }

                return $user->getAllPermissions()->pluck('name')->values()->all();
            },
        ];
    }
}

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
     * The URL prefix the app is mounted under, with no trailing slash.
     *
     * Derived from the request rather than configuration so the same build
     * works at the domain root and under a subdirectory: "" on
     * `php artisan serve`, "/p/public" under the WAMP document root.
     */
    protected function basePath(Request $request): string
    {
        // getBaseUrl() is the directory the front controller lives in, e.g.
        // "/p/public" or "/p". Laravel keeps this in step with the request.
        return rtrim(str_replace('\\', '/', $request->getBaseUrl()), '/');
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

            /*
             * Where the app is mounted, e.g. "/p/public" under WAMP or "" when
             * the document root already points at public/.
             *
             * Every in-app link is written root-relative ("/facilities"), which
             * a browser resolves against the domain root and so silently drops
             * the mount point. The front end prefixes these with basePath; see
             * withBasePath() in resources/js/lib/format.ts.
             */
            'basePath' => $this->basePath($request),

            /*
             * Nav hrefs stay unprefixed in config so they remain the plain route
             * names ("/facilities") in one place. The front end prefixes them
             * with basePath when rendering -- see withBasePath() in
             * resources/js/lib/format.ts.
             */
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

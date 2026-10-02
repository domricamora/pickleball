<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Landing page after sign-in.
     *
     * Platform staff see every tenant; tenant staff see only their own, which
     * the BelongsToOrganization scope guarantees at the query level.
     */
    public function index(): Response
    {
        $user = request()->user();

        $tenants = Organization::query()
            ->withCount('branches')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'status', 'address_city']);

        return Inertia::render('Dashboard', [
            'role' => $user->getRoleNames()->first(),
            'isPlatformStaff' => $user->isPlatformStaff(),
            'organization' => $user->organization?->only(['id', 'name', 'slug', 'status']),
            'branch' => $user->branch?->only(['id', 'name', 'address_city']),
            'stats' => [
                // Branch::count() respects the tenant scope, so this is the
                // number of locations the viewer is allowed to see.
                'branches' => Branch::query()->count(),
                'staff' => User::query()->count(),
            ],
            'tenants' => $tenants,
        ]);
    }
}

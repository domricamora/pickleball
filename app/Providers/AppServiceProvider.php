<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Policies\BookingPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CourtPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Authorisation is always checked server-side via these policies.
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Court::class, CourtPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
    }
}

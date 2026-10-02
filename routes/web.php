<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Site\MarketingController;
use App\Http\Controllers\Site\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing site (plan.md §9)
|--------------------------------------------------------------------------
*/

Route::get('/', [MarketingController::class, 'home'])->name('home');

Route::get('/facilities', [MarketingController::class, 'facilities'])->name('facilities.index');
Route::get('/courts', [MarketingController::class, 'courts'])->name('courts.index');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/memberships', [MarketingController::class, 'memberships'])->name('memberships');
Route::get('/events', [MarketingController::class, 'events'])->name('events.index');
Route::get('/tournaments', [MarketingController::class, 'tournaments'])->name('tournaments.index');
Route::get('/about', [MarketingController::class, 'about'])->name('about');
Route::get('/contact', [MarketingController::class, 'contact'])->name('contact');
Route::get('/faq', [MarketingController::class, 'faq'])->name('faq');
Route::get('/blog', [MarketingController::class, 'blog'])->name('blog.index');

/*
|--------------------------------------------------------------------------
| Booking funnel
|--------------------------------------------------------------------------
| Availability and slot selection. Payment capture is Phase 5 (plan.md §13).
*/

Route::get('/book', [BookingController::class, 'show'])->name('book.index');
Route::post('/book', [BookingController::class, 'store'])->name('book.store');

/*
|--------------------------------------------------------------------------
| Application (authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware([
    config('fortify.auth_middleware'),
    'active',
    'verified',
])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Administration (authenticated, tenant-scoped)
|--------------------------------------------------------------------------
| Every route here is behind a policy check inside its controller. The
| BelongsToOrganization scope means a query can only ever return the caller's
| own rows, so a guessed id from another tenant resolves to 404.
*/

Route::middleware([
    config('fortify.auth_middleware'),
    'active',
    'verified',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // Facilities (plan.md §11)
        Route::get('/facilities', [BranchController::class, 'index'])->name('facilities.index');
        Route::get('/facilities/create', [BranchController::class, 'create'])->name('facilities.create');
        Route::post('/facilities', [BranchController::class, 'store'])->name('facilities.store');
        Route::get('/facilities/{branch}/edit', [BranchController::class, 'edit'])->name('facilities.edit');
        Route::put('/facilities/{branch}', [BranchController::class, 'update'])->name('facilities.update');
        Route::delete('/facilities/{branch}', [BranchController::class, 'destroy'])->name('facilities.destroy');

        // Courts (plan.md §11)
        Route::get('/courts', [CourtController::class, 'index'])->name('courts.index');
        Route::get('/courts/create', [CourtController::class, 'create'])->name('courts.create');
        Route::post('/courts', [CourtController::class, 'store'])->name('courts.store');
        Route::get('/courts/{court}', [CourtController::class, 'show'])->name('courts.show');
        Route::get('/courts/{court}/edit', [CourtController::class, 'edit'])->name('courts.edit');
        Route::put('/courts/{court}', [CourtController::class, 'update'])->name('courts.update');
        Route::delete('/courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');
    });

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| Fortify registers its own routes (login, register, password reset, email
| verification, two-factor) from its service provider. Its Blade views are
| pointed at Inertia pages in App\Providers\FortifyServiceProvider.
*/

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

<?php

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
| Booking entry point
|--------------------------------------------------------------------------
| The booking engine itself is Phase 4. This route renders the public
| "Book a Court" landing page for now.
*/

Route::view('/book', 'book-landing')->name('book');

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

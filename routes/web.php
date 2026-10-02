<?php

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
| SEO
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

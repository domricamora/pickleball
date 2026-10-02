<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        /*
         | Fortify ships Blade views. The whole public site is React via Inertia,
         | so every auth screen is pointed at an Inertia page instead.
         */
        Fortify::loginView(fn () => Inertia::render('auth/Login', ['canResetPassword' => true]));
        Fortify::registerView(fn () => Inertia::render('auth/Register'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/ForgotPassword'));
        /*
         | The reset token is a *route* parameter
         | (`/reset-password/{token}`), so it must be read with `route()` —
         | `$request->input()` only looks at query and body. The address
         | arrives as a query string and falls back to the signed-in user.
         */
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->string('email')->toString()
                ?: (string) ($request->user()->email ?? ''),
            'token' => (string) ($request->route('token') ?? ''),
        ]));
        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->string('status')->toString(),
        ]));
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        /*
         | Fortify has no notion of a suspended account, so block one before a
         | session is ever created. The message is deliberately identical to a
         | normal failure so it never reveals whether the address exists.
         |
         | The callback must return the User (Fortify calls login() itself) or
         | null to fail. `Fortify::username()` doubles as a getter and a setter,
         | so it is left at its default rather than overridden with a closure.
         */
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->input(Fortify::username()))->first();

            if ($user === null || ! $user->isActive()) {
                return null;
            }

            // Fortify performs the login itself once a user is returned.
            return Auth::validate($request->only(Fortify::username(), 'password')) ? $user : null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });

        /*
        |----------------------------------------------------------------------
        | Application rate limits (plan.md §24)
        |----------------------------------------------------------------------
        | Public booking is the most attractive thing to hammer, so it gets
        | the tightest limit. The rest are generous enough not to get in a
        | real player's way.
        */

        RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('public-pages', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}

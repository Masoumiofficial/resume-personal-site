<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Responses\UniformPasswordResetLinkResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // No account enumeration via "forgot password" (bound in boot() so it
        // always overrides Fortify's own binding).
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, UniformPasswordResetLinkResponse::class);

        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // 5 attempts / minute per (email + IP), plus 20 / hour per IP across all emails.
        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower((string) $request->input(Fortify::username())));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perHour(20)->by('login-ip|'.$request->ip()),
            ];
        });

        // Keyed by the pending login AND the IP so one source cannot spread
        // guesses across fresh sessions.
        RateLimiter::for('two-factor', function (Request $request) {
            return [
                Limit::perMinute(5)->by('2fa|'.$request->session()->get('login.id').'|'.$request->ip()),
                Limit::perHour(30)->by('2fa-ip|'.$request->ip()),
            ];
        });

        // Passkeys are disabled in config/fortify.php; a limiter is still defined
        // because Fortify references it by name.
        RateLimiter::for('passkeys', fn (Request $request) => Limit::perMinute(10)->by('passkeys|'.$request->ip()));
    }
}

<?php

namespace App\Providers;

use App\Listeners\RecordAuthenticationActivity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 191 × 4 bytes (utf8mb4) = 764 bytes: fits the 767-byte index limit of
        // older InnoDB row formats, so unique indexes work on any shared host.
        Schema::defaultStringLength(191);

        // Fail loudly in development/tests on lazy loading, silently discarded
        // attributes and missing attributes. Never in production.
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(12)->max(128));

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        foreach ([
            Login::class, Failed::class, Lockout::class, Logout::class, PasswordReset::class, PasswordUpdatedViaController::class,
            TwoFactorAuthenticationConfirmed::class, TwoFactorAuthenticationDisabled::class,
            TwoFactorAuthenticationFailed::class, RecoveryCodesGenerated::class, RecoveryCodeReplaced::class,
        ] as $event) {
            Event::listen($event, RecordAuthenticationActivity::class);
        }
    }
}

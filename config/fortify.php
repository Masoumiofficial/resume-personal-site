<?php

use Laravel\Fortify\Features;

/*
| Fortify provides the authentication back end (login, password reset,
| two-factor). Views are our own Blade templates (see FortifyServiceProvider).
| There is deliberately NO public registration: the single admin account is
| created with `php artisan app:create-admin`.
*/

return [

    'guard' => 'web',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    // Where users land after login.
    'home' => '/'.config('admin.path'),

    // All auth routes live under the admin prefix: /{ADMIN_PATH}/login, …
    'prefix' => config('admin.path'),

    'domain' => null,

    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    'views' => true,

    // Passkeys are not enabled (see features below); the block is kept so the
    // Fortify service provider receives sane values.
    'passkeys' => [
        'relying_party_id' => parse_url((string) config('app.url'), PHP_URL_HOST),
        'allowed_origins' => [config('app.url')],
        'timeout' => 60000,
    ],

    'features' => [
        Features::resetPasswords(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],

];

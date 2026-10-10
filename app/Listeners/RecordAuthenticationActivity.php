<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\IpHash;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Writes security-relevant authentication events to activity_logs.
 *
 * Privacy rules: never the password, never the raw attempted username (people
 * sometimes type their password into the email field), never the raw IP.
 */
class RecordAuthenticationActivity
{
    public function __construct(private readonly Request $request) {}

    public function handle(object $event): void
    {
        $name = match (true) {
            $event instanceof Login => 'auth.login',
            $event instanceof Failed => 'auth.failed',
            $event instanceof Lockout => 'auth.lockout',
            $event instanceof Logout => 'auth.logout',
            $event instanceof PasswordReset => 'auth.password_reset',
            $event instanceof PasswordUpdatedViaController => 'auth.password_changed',
            $event instanceof TwoFactorAuthenticationConfirmed => 'auth.two_factor_enabled',
            $event instanceof TwoFactorAuthenticationDisabled => 'auth.two_factor_disabled',
            $event instanceof TwoFactorAuthenticationFailed => 'auth.two_factor_failed',
            $event instanceof RecoveryCodesGenerated => 'auth.recovery_codes_generated',
            $event instanceof RecoveryCodeReplaced => 'auth.recovery_code_used', // the code itself is never stored
            default => null,
        };

        if ($name === null) {
            return;
        }

        $user = property_exists($event, 'user') ? $event->user : null;
        $properties = [];

        if ($event instanceof Failed) {
            $properties['known_account'] = $user !== null;
            $attempted = $event->credentials['email'] ?? null;
            if (is_string($attempted) && $attempted !== '') {
                // Keyed hash only: allows "same identifier attacked N times" without storing what was typed.
                $properties['identifier_hash'] = substr(hash_hmac('sha256', 'id|'.mb_strtolower($attempted), (string) config('app.key')), 0, 16);
            }
        }

        if ($event instanceof Login && $user instanceof User) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
            $properties['remember'] = (bool) $event->remember;
        }

        $request = $event instanceof Lockout ? $event->request : $this->request;

        $agent = (string) $request->userAgent();

        ActivityLog::create([
            'user_id' => $user instanceof User ? $user->getKey() : null,
            'event' => $name,
            'ip_hash' => IpHash::make($request->ip()),
            'properties' => $properties + ($agent !== '' ? ['agent' => mb_substr($agent, 0, 120)] : []),
        ]);
    }
}

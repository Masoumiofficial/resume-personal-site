<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Account security: password change and two-factor setup. All POST/DELETE
 * endpoints are Fortify's; enabling/disabling 2FA requires a recent password
 * confirmation (config/fortify.php → confirmPassword).
 */
class SecurityController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return view('admin.security', [
            'user' => $user,
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPending' => $pending,
            'qrSvg' => $pending ? $user->twoFactorQrCodeSvg() : null,
            'recoveryCodes' => session('status') === 'recovery-codes-generated' || session('status') === 'two-factor-authentication-confirmed'
                ? $user->recoveryCodes()
                : null,
        ]);
    }
}

<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        // Password::default() = min 12, max 128 (AppServiceProvider).
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}

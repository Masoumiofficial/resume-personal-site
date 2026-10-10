<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * URL segment: lowercase Latin letters, digits and Persian letters, separated
 * by single hyphens. Persian is allowed because existing tag URLs use it and
 * must survive the migration unchanged.
 */
class Slug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[a-z0-9\x{0600}-\x{06FF}\x{200C}]+(?:-[a-z0-9\x{0600}-\x{06FF}\x{200C}]+)*$/u', $value)) {
            $fail('نامک فقط می‌تواند حروف کوچک لاتین، حروف فارسی، رقم و خط تیره (-) داشته باشد؛ بدون فاصله.');
        }
    }
}

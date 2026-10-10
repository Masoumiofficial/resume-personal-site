<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Spam signals for the public contact form (no third-party captcha):
 *  - honeypot: a visually hidden "website" field humans leave empty
 *  - time trap: an encrypted render timestamp; < 3 s or > 2 days is suspicious
 */
final class ContactForm
{
    public const HONEYPOT = 'website';

    public const TOKEN = '_ts';

    public const MIN_SECONDS = 3;

    public const MAX_SECONDS = 172800;

    /** Value for the hidden time-trap field, rendered with the form. */
    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** Seconds since the form was rendered, or null when the token is missing/forged. */
    public static function elapsed(?string $token): ?int
    {
        if (! is_string($token) || $token === '') {
            return null;
        }
        try {
            $rendered = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return null;
        }

        return $rendered > 0 ? time() - $rendered : null;
    }

    public static function linkCount(string $text): int
    {
        return preg_match_all('#(https?://|www\.)#i', $text);
    }
}

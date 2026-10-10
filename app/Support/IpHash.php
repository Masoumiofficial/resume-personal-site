<?php

namespace App\Support;

/**
 * Keyed hash of a client IP. Lets us correlate abuse (same source, many
 * attempts) without storing raw IP addresses. The key is derived from
 * APP_KEY, so hashes cannot be reversed by brute-forcing the IPv4 space
 * without the key.
 */
final class IpHash
{
    public static function make(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', 'ip|'.$ip, (string) config('app.key'));
    }
}

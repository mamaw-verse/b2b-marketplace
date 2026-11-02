<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Cookie;

final class HumanPass
{
    public const COOKIE_NAME = 'human_pass';

    public static function issue(Request $request): Cookie
    {
        $now = time();
        $ttl = (int) config('humancheck.ttl', 1200);

        $payload = [
            'iat' => $now,
            'exp' => $now + $ttl,
            'ua_hash' => hash('sha256', (string) $request->userAgent()),
        ];

        if ((bool) config('humancheck.bind_ip', false)) {
            $payload['ip_hash'] = hash('sha256', (string) $request->ip());
        }

        $encrypted = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));

        return cookie(
            name: self::COOKIE_NAME,
            value: $encrypted,
            minutes: (int) ceil($ttl / 60),
            path: '/',
            domain: null,
            secure: app()->isProduction(),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax',
        );
    }

    public static function valid(Request $request): bool
    {
        if (! (bool) config('humancheck.enabled', true)) {
            return true;
        }

        $raw = $request->cookie(self::COOKIE_NAME);
        if ($raw === null || $raw === '') {
            return false;
        }

        try {
            $decrypted = Crypt::decryptString($raw);
            /** @var array{iat?: int, exp?: int, ua_hash?: string, ip_hash?: string} $payload */
            $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($payload['exp'], $payload['iat'], $payload['ua_hash'])) {
                return false;
            }

            if (time() > $payload['exp']) {
                return false;
            }

            $currentUaHash = hash('sha256', (string) $request->userAgent());
            if (! hash_equals($payload['ua_hash'], $currentUaHash)) {
                return false;
            }

            if ((bool) config('humancheck.bind_ip', false)) {
                if (! isset($payload['ip_hash'])) {
                    return false;
                }

                $currentIpHash = hash('sha256', (string) $request->ip());
                if (! hash_equals($payload['ip_hash'], $currentIpHash)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function forget(): Cookie
    {
        return cookie()->forget(self::COOKIE_NAME);
    }

    public static function expiresAt(Request $request): ?int
    {
        $raw = $request->cookie(self::COOKIE_NAME);
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($raw);
            /** @var array{exp?: int} $payload */
            $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);

            return $payload['exp'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}

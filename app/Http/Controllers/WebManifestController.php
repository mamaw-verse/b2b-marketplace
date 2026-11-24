<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Minimal web app manifest. It names the site and supplies icons and a colour; it is
 * deliberately `display: browser` so no install prompt is implied (this is not a PWA).
 */
final class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $name = (string) config('seo.site_name');

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'start_url' => '/',
            'scope' => '/',
            'display' => 'browser',
            'theme_color' => config('seo.theme_color'),
            'background_color' => '#ffffff',
            'icons' => [
                ['src' => asset('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset(config('seo.logo_path')), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=86400',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

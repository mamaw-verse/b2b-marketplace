<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Http\Request;

final class CanonicalUrl
{
    /**
     * Absolute, query-stripped canonical for the request. Tracking and filter
     * parameters (utm_*, fbclid, gclid, ref, search, category, ...) never
     * survive; only a pagination `page` greater than 1 is kept.
     */
    public static function fromRequest(Request $request): string
    {
        $path = $request->path();
        $url = url($path === '/' ? '/' : $path);

        $page = (int) $request->query('page', 1);

        return $page > 1 ? $url.'?page='.$page : $url;
    }
}

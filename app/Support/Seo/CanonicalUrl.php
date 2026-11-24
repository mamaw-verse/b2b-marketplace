<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Http\Request;

final class CanonicalUrl
{
    /**
     * Absolute, query-stripped canonical for the request. Tracking and filter
     * parameters (utm_*, fbclid, gclid, ref, search, category, ...) never
     * survive; only a pagination `page` greater than 1 is kept. A request
     * carrying a facet parameter canonicalises to the clean listing URL, with
     * no page either (T3.2).
     */
    public static function fromRequest(Request $request): string
    {
        $path = $request->path();
        $url = url($path === '/' ? '/' : $path);

        if (app(IndexingPolicy::class)->hasFacetParams($request)) {
            return $url;
        }

        return self::withPage($url, (int) $request->query('page', 1));
    }

    /**
     * `$url` itself on page 1, `$url?page=N` beyond it (paginated pages are self-canonical).
     */
    public static function withPage(string $url, int $page): string
    {
        return $page > 1 ? $url.'?page='.$page : $url;
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Http\Request;

/**
 * Decides what search engines may crawl/index. Reads config/seo.php only.
 */
final class IndexingPolicy
{
    /**
     * Whether this deployment may be indexed at all.
     */
    public function indexingAllowed(): bool
    {
        return app()->environment('production') && ! config('seo.force_noindex');
    }

    /**
     * Private path prefixes, normalised (no leading/trailing slashes).
     *
     * @return list<string>
     */
    public function privatePathPatterns(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $pattern): string => trim((string) $pattern, '/'),
            (array) config('seo.private_path_patterns', []),
        ), static fn (string $pattern): bool => $pattern !== '')));
    }

    public function isPrivatePath(Request $request): bool
    {
        foreach ($this->privatePathPatterns() as $pattern) {
            if ($request->is($pattern, $pattern.'/*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Query parameters that turn a listing URL into a facet/search/sort state.
     *
     * @return list<string>
     */
    public function facetQueryParams(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $param): string => trim((string) $param),
            (array) config('seo.noindex_follow_query_params', []),
        ), static fn (string $param): bool => $param !== '')));
    }

    /**
     * Whether the request carries a search, filter or sort parameter (present,
     * even when empty): such URLs are `noindex, follow` and canonicalise to the
     * clean listing URL. Pagination and tracking parameters are not facets.
     */
    public function hasFacetParams(Request $request): bool
    {
        foreach ($this->facetQueryParams() as $param) {
            if ($request->query->has($param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Value for `X-Robots-Tag` / `<meta name="robots">` on this request, or
     * null when the page may be indexed as-is.
     */
    public function robotsDirective(Request $request): ?string
    {
        if (! $this->indexingAllowed() || $this->isPrivatePath($request)) {
            return 'noindex, nofollow';
        }

        if ($this->hasFacetParams($request)) {
            return 'noindex, follow';
        }

        return null;
    }
}

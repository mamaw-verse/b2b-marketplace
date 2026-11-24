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
     * Value for `X-Robots-Tag` on this request, or null when indexing is fine.
     */
    public function robotsDirective(Request $request): ?string
    {
        if (! $this->indexingAllowed() || $this->isPrivatePath($request)) {
            return 'noindex, nofollow';
        }

        return null;
    }
}

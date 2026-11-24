<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache for generated sitemap XML.
 *
 * Entries are keyed by a version number; flush() bumps it, which invalidates
 * every sitemap at once without needing cache tags (works on any store). A
 * TTL remains as a safety net for mass updates that bypass model events.
 */
final class SitemapCache
{
    private const VERSION_KEY = 'seo:sitemap:version';

    public function remember(string $name, Closure $build): string
    {
        return Cache::remember(
            sprintf('seo:sitemap:%d:%s', $this->version(), $name),
            max(60, (int) config('seo.sitemap.cache_ttl', 3600)),
            $build,
        );
    }

    public function flush(): void
    {
        Cache::add(self::VERSION_KEY, 1);
        Cache::increment(self::VERSION_KEY);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}

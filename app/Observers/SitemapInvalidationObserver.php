<?php

declare(strict_types=1);

namespace App\Observers;

use App\Support\Seo\SitemapCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Drops the cached sitemaps whenever something they depend on changes:
 * products, shops, categories, certificates and subscriptions (a subscription
 * change flips shop and product visibility).
 */
final class SitemapInvalidationObserver
{
    /**
     * Wait for the surrounding transaction to commit, so a request that
     * rebuilds the sitemap right after the flush cannot cache pre-commit data.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly SitemapCache $cache) {}

    public function saved(Model $model): void
    {
        $this->cache->flush();
    }

    public function deleted(Model $model): void
    {
        $this->cache->flush();
    }

    public function restored(Model $model): void
    {
        $this->cache->flush();
    }
}

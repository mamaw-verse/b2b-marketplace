<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Seo\Sitemap\BuildSitemapCategories;
use App\Actions\Seo\Sitemap\BuildSitemapCompanies;
use App\Actions\Seo\Sitemap\BuildSitemapIndex;
use App\Actions\Seo\Sitemap\BuildSitemapPages;
use App\Actions\Seo\Sitemap\BuildSitemapProducts;
use App\Support\Seo\SitemapCache;
use Illuminate\Http\Response;

/**
 * Generated sitemaps (T3.3). Nothing is written to `public/`; every file is
 * built on demand from the visibility rules and cached until a model change
 * flushes it.
 */
final class SitemapController extends Controller
{
    public function __construct(private readonly SitemapCache $cache)
    {
        abort_unless(config('seo.sitemap_enabled'), 404);
    }

    public function index(BuildSitemapIndex $build): Response
    {
        return $this->xml($this->cache->remember('index', fn (): string => $build->handle()));
    }

    public function pages(BuildSitemapPages $build): Response
    {
        return $this->xml($this->cache->remember('pages', fn (): string => $build->handle()));
    }

    public function categories(BuildSitemapCategories $build): Response
    {
        abort_unless($build->hasEntries(), 404);

        return $this->xml($this->cache->remember('categories', fn (): string => $build->handle()));
    }

    public function companies(BuildSitemapCompanies $build): Response
    {
        abort_unless($build->hasEntries(), 404);

        return $this->xml($this->cache->remember('companies', fn (): string => $build->handle()));
    }

    public function products(BuildSitemapProducts $build, int $page): Response
    {
        abort_unless($page >= 1 && $page <= $build->fileCount(), 404);

        return $this->xml($this->cache->remember('products-'.$page, fn (): string => $build->handle($page)));
    }

    private function xml(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

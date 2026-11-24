<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Seo\BuildLlmsTxt;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\SitemapCache;
use Illuminate\Http\Response;

/**
 * /llms.txt (T5.2). Like robots.txt it is generated, never written to `public/`, and it
 * only exists where indexing is allowed (404 on non-production and under force_noindex).
 * It is cached with the sitemaps (per locale, so a visitor's locale never leaks into the
 * crawler copy), so any change that flushes them refreshes it too.
 */
final class LlmsTxtController extends Controller
{
    public function __invoke(IndexingPolicy $policy, SitemapCache $cache, BuildLlmsTxt $build): Response
    {
        abort_unless($policy->indexingAllowed(), 404);

        return response($cache->remember('llms:'.app()->getLocale(), fn (): string => $build->handle()), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}

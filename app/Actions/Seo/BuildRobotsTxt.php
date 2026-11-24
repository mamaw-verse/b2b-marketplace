<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Support\Seo\IndexingPolicy;

final class BuildRobotsTxt
{
    public function __construct(private readonly IndexingPolicy $policy) {}

    public function handle(): string
    {
        if (! $this->policy->indexingAllowed()) {
            return "User-agent: *\nDisallow: /\n";
        }

        $lines = ['User-agent: *'];

        foreach ($this->policy->privatePathPatterns() as $pattern) {
            $lines[] = 'Disallow: /'.$pattern;
        }

        if (config('seo.sitemap_enabled')) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.url('/sitemap.xml');
        }

        return implode("\n", $lines)."\n";
    }
}

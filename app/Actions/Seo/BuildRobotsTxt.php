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

        $private = array_map(
            static fn (string $pattern): string => 'Disallow: /'.$pattern,
            $this->policy->privatePathPatterns(),
        );

        $groups = [$this->group('*', $private)];

        foreach ((array) config('seo.ai_crawlers.search', []) as $agent) {
            $groups[] = $this->group((string) $agent, $private);
        }

        foreach ((array) config('seo.ai_crawlers.training', []) as $agent) {
            $groups[] = $this->group(
                (string) $agent,
                config('seo.block_ai_training') ? ['Disallow: /'] : $private,
            );
        }

        $body = implode("\n\n", $groups);

        if (config('seo.sitemap_enabled')) {
            $body .= "\n\nSitemap: ".url('/sitemap.xml');
        }

        return $body."\n";
    }

    /**
     * One named group. A named group replaces `User-agent: *` for that bot, so the
     * private paths are always repeated inside it (RFC 9309).
     *
     * @param  list<string>  $rules
     */
    private function group(string $agent, array $rules): string
    {
        return implode("\n", ['User-agent: '.$agent, ...$rules]);
    }
}

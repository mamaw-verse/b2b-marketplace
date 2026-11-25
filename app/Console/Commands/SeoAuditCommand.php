<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Seo\AuditSeo;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\SitemapXml;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SeoAuditCommand extends Command
{
    protected $signature = 'seo:audit
        {--url= : Origin to audit (defaults to APP_URL)}
        {--sample=5 : Pages sampled from each sitemap child}
        {--allow-noindex : Staging mode: skip checks that only hold where indexing is allowed}';

    protected $description = 'Audit the live site: robots.txt, llms.txt, sitemaps, page heads, JSON-LD and image URLs';

    public function handle(AuditSeo $audit, IndexingPolicy $policy): int
    {
        $origin = (string) ($this->option('url') ?: SitemapXml::origin());
        $allowNoindex = (bool) $this->option('allow-noindex');

        if (! $allowNoindex && ! $policy->indexingAllowed()) {
            $this->error('Indexing is disabled here (non-production or SEO_FORCE_NOINDEX). Use --allow-noindex to audit a staging deploy.');

            return self::FAILURE;
        }

        $result = $audit->handle($origin, (int) $this->option('sample'), $allowNoindex);

        $this->info('Checked '.$result['checked'].' URL(s) on '.$origin.'.');

        if ($result['errors'] === []) {
            $this->info('No SEO regressions found.');

            return self::SUCCESS;
        }

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        Log::error('seo:audit found '.count($result['errors']).' problem(s) on '.$origin, ['errors' => $result['errors']]);

        return self::FAILURE;
    }
}

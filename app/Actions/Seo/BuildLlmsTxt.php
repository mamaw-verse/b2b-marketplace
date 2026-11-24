<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Actions\Seo\Sitemap\BuildSitemapCategories;
use App\Support\Seo\SitemapXml;
use App\Support\Seo\TrustFacts;
use Illuminate\Support\Facades\Route;

/**
 * Builds /llms.txt (T5.2): a short markdown guide for answer engines.
 *
 * It is a low-cost, unproven signal and is not claimed to affect ranking. It lists
 * public URLs only (the same routes and categories the sitemap exposes, with the
 * origin taken from APP_URL like the sitemap) and states only what TrustFacts allows.
 */
final class BuildLlmsTxt
{
    /**
     * Route name => link text and one-line description. Routes that do not exist are skipped.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array KEY_PAGES = [
        'home' => ['Home', 'Search for halal products and suppliers.'],
        'about' => ['About', 'What the marketplace is and how it works.'],
        'help.faq' => ['FAQ', 'Answers to common buyer and supplier questions.'],
        'legal.terms' => ['Terms of use', 'The terms that apply to using the marketplace.'],
        'legal.privacy' => ['Privacy policy', 'How personal data is handled.'],
    ];

    private const array DIRECTORIES = [
        'storefront.products' => ['Product directory', 'Browse products listed by verified suppliers.'],
        'storefront.companies' => ['Company directory', 'Browse the supplier directory.'],
    ];

    public function __construct(
        private readonly TrustFacts $facts,
        private readonly BuildSitemapCategories $categories,
    ) {}

    public function handle(): string
    {
        $name = $this->text((string) config('seo.site_name'));

        $lines = [
            '# '.$name,
            '',
            '> '.__(':app is a B2B marketplace that connects buyers with verified halal-certified suppliers, manufacturers and products across the halal supply chain.', ['app' => $name]),
        ];

        $sentences = $this->facts->sentences();

        if ($sentences !== []) {
            array_push($lines, '', '## '.__('How it works'), '');
            array_push($lines, ...array_map(static fn (string $sentence): string => '- '.$sentence, $sentences));
        }

        array_push($lines, '', '## '.__('Key pages'), '', ...$this->links(self::KEY_PAGES));
        array_push($lines, '', '## '.__('Directories'), '', ...$this->links(self::DIRECTORIES));

        $categories = $this->categories->topLevel();

        if ($categories->isNotEmpty()) {
            array_push($lines, '', '## '.__('Product categories'), '');
            array_push($lines, ...$categories->map(
                fn (array $category): string => '- ['.$this->text($category['name']).']('.$category['loc'].')',
            )->all());
        }

        if (config('seo.sitemap_enabled')) {
            array_push($lines, '', '## '.__('Optional'), '', '- ['.__('Sitemap').']('.SitemapXml::url('/sitemap.xml').')');
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $pages
     * @return list<string>
     */
    private function links(array $pages): array
    {
        $links = [];

        foreach ($pages as $route => [$title, $description]) {
            if (! Route::has($route)) {
                continue;
            }

            $links[] = '- ['.__($title).']('.SitemapXml::url(route($route, absolute: false)).'): '.__($description);
        }

        return $links;
    }

    /**
     * Single-line, markdown-safe text for names that come from the database.
     */
    private function text(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace(['[', ']'], ['(', ')'], $value)));
    }
}

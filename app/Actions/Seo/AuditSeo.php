<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Support\Schema\JsonLdValidator;
use App\Support\Seo\IndexingPolicy;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Live SEO audit (T6.4). Fetches the deployed site over HTTP, exactly as a crawler
 * would, and reports regressions: crawl-control files, sitemaps, and a sample of
 * pages (head tags, JSON-LD via JsonLdValidator, image URLs).
 *
 * Pure reporting: it never writes anything and returns the problems it found, so
 * the console command (and the weekly schedule) decide how to surface them.
 */
final class AuditSeo
{
    private const int MAX_IMAGE_BYTES = 5 * 1_048_576;

    private const int MIN_IMAGE_WIDTH = 600;

    private const int MIN_IMAGE_HEIGHT = 315;

    public function __construct(
        private readonly IndexingPolicy $policy,
        private readonly JsonLdValidator $validator,
    ) {}

    /**
     * @param  int  $sample  How many URLs to sample per sitemap child (categories, companies, products).
     * @param  bool  $allowNoindex  Staging mode: skip checks that only hold where indexing is allowed.
     * @return array{checked: int, errors: list<string>}
     */
    public function handle(string $origin, int $sample = 5, bool $allowNoindex = false): array
    {
        $origin = rtrim($origin, '/');
        $errors = [];
        $checked = 0;

        $checked += $this->auditRobots($origin, $allowNoindex, $errors);
        $checked += $this->auditLlms($origin, $allowNoindex, $errors);

        $pages = $this->staticPages($origin);
        $imageUrls = [];

        [$sitemapChecked, $sampled] = $this->auditSitemaps($origin, max(1, $sample), $errors);
        $checked += $sitemapChecked;

        foreach (array_values(array_unique([...$pages, ...$sampled])) as $url) {
            $checked++;
            array_push($imageUrls, ...$this->auditPage($url, $allowNoindex, $errors));
        }

        foreach (array_values(array_unique($imageUrls)) as $image) {
            $checked++;
            $this->auditImage($image, $errors);
        }

        return ['checked' => $checked, 'errors' => $errors];
    }

    /**
     * @param  list<string>  $errors
     */
    private function auditRobots(string $origin, bool $allowNoindex, array &$errors): int
    {
        $response = $this->get($origin.'/robots.txt', $errors, 'robots.txt');

        if ($response === null) {
            return 1;
        }

        $body = $response->body();

        if (! $allowNoindex) {
            $groups = preg_split('/\R{2,}/', trim($body)) ?: [];

            if (preg_match('/^Disallow:\s*\/\s*$/m', (string) ($groups[0] ?? '')) === 1) {
                $errors[] = 'robots.txt blocks the whole site for User-agent: * (is this a non-production or force_noindex deploy?).';
            }

            if (config('seo.sitemap_enabled') && ! str_contains($body, 'Sitemap: ')) {
                $errors[] = 'robots.txt has no Sitemap: line.';
            }

            foreach ($this->policy->privatePathPatterns() as $pattern) {
                if (! str_contains($body, 'Disallow: /'.$pattern."\n")) {
                    $errors[] = 'robots.txt does not disallow /'.$pattern.'.';
                }
            }
        }

        return 1;
    }

    /**
     * @param  list<string>  $errors
     */
    private function auditLlms(string $origin, bool $allowNoindex, array &$errors): int
    {
        if ($allowNoindex) {
            return 0;
        }

        $response = $this->get($origin.'/llms.txt', $errors, 'llms.txt');

        if ($response !== null && ! str_starts_with(ltrim($response->body()), '# ')) {
            $errors[] = 'llms.txt does not start with a markdown title.';
        }

        return 1;
    }

    /**
     * Fetches the sitemap index and every child, then samples URLs from each.
     *
     * @param  list<string>  $errors
     * @return array{0: int, 1: list<string>} Number of requests made and the sampled page URLs.
     */
    private function auditSitemaps(string $origin, int $sample, array &$errors): array
    {
        if (! config('seo.sitemap_enabled')) {
            return [0, []];
        }

        $checked = 1;
        $index = $this->get($origin.'/sitemap.xml', $errors, 'sitemap.xml');

        if ($index === null) {
            return [$checked, []];
        }

        $children = $this->locs($index->body(), 'sitemap.xml', $errors);
        $sampled = [];

        foreach ($children as $child) {
            $checked++;
            $response = $this->get($child, $errors, $child);

            if ($response === null) {
                continue;
            }

            $urls = $this->locs($response->body(), $child, $errors);

            // Static pages are audited separately; sample entity pages only.
            if (str_contains($child, 'sitemap-pages')) {
                continue;
            }

            if (count($urls) > $sample) {
                shuffle($urls);
                $urls = array_slice($urls, 0, $sample);
            }

            array_push($sampled, ...$urls);
        }

        return [$checked, $sampled];
    }

    /**
     * @return list<string>
     */
    private function staticPages(string $origin): array
    {
        $pages = [];

        foreach ((array) config('seo.sitemap.static_routes', []) as $route) {
            if (Route::has((string) $route)) {
                $pages[] = $origin.route((string) $route, absolute: false);
            }
        }

        return $pages;
    }

    /**
     * Audits one page and returns the image URLs it advertises.
     *
     * @param  list<string>  $errors
     * @return list<string>
     */
    private function auditPage(string $url, bool $allowNoindex, array &$errors): array
    {
        $response = $this->get($url, $errors, $url);

        if ($response === null) {
            return [];
        }

        $html = $response->body();
        $label = $url;

        foreach ($this->validator->validateHtml($html) as $problem) {
            $errors[] = $label.': JSON-LD: '.$problem;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);

        $title = trim((string) $xpath->evaluate('string(//head/title)'));

        if ($title === '') {
            $errors[] = $label.': missing <title>.';
        }

        if (trim((string) $xpath->evaluate('string(//meta[@name="description"]/@content)')) === '') {
            $errors[] = $label.': missing meta description.';
        }

        $canonicals = $xpath->query('//link[@rel="canonical"]');
        $canonicalCount = $canonicals === false ? 0 : $canonicals->length;

        if ($canonicalCount !== 1) {
            $errors[] = $label.': expected exactly one canonical link, found '.$canonicalCount.'.';
        } else {
            $canonical = (string) $xpath->evaluate('string(//link[@rel="canonical"]/@href)');

            if (! str_starts_with($canonical, 'http') || str_contains($canonical, '?') || str_contains($canonical, 'localhost')) {
                $errors[] = $label.': canonical must be an absolute, query-free URL (got '.$canonical.').';
            }
        }

        if (! $allowNoindex) {
            $robots = strtolower((string) $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
            $header = strtolower((string) $response->header('X-Robots-Tag'));

            if (str_contains($robots, 'noindex') || str_contains($header, 'noindex')) {
                // Empty category pages and facet URLs are legitimately noindex; flag only
                // sampled pages the sitemap itself advertises.
                $errors[] = $label.': page is noindex although it is listed for crawling.';
            }
        }

        $images = [];

        foreach (['og:image', 'twitter:image'] as $property) {
            $value = trim((string) $xpath->evaluate('string(//meta[@property="'.$property.'" or @name="'.$property.'"]/@content)'));

            if ($value === '') {
                $errors[] = $label.': missing '.$property.'.';
            } else {
                $images[] = $value;
            }
        }

        return $images;
    }

    /**
     * Fetches one advertised social image and checks it the way a link-preview
     * crawler would (T4.2): HTTP 200, a readable raster image, under the size
     * cap and at least the minimum large-card dimensions. 1200x630 is the
     * recommendation, so anything smaller than the floor is an error and
     * anything between the floor and 1200 wide is accepted.
     *
     * @param  list<string>  $errors
     */
    private function auditImage(string $url, array &$errors): void
    {
        $response = $this->get($url, $errors, 'Image '.$url);

        if ($response === null) {
            return;
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_IMAGE_BYTES) {
            $errors[] = 'Image '.$url.' is '.round(strlen($body) / 1_048_576, 1).' MB; keep social images under 5 MB.';
        }

        $size = @getimagesizefromstring($body);

        if ($size === false) {
            $errors[] = 'Image '.$url.' is not a readable raster image.';

            return;
        }

        if ($size[0] < self::MIN_IMAGE_WIDTH || $size[1] < self::MIN_IMAGE_HEIGHT) {
            $errors[] = 'Image '.$url.' is '.$size[0].'x'.$size[1].'; social previews need at least '
                .self::MIN_IMAGE_WIDTH.'x'.self::MIN_IMAGE_HEIGHT.' (1200x630 recommended).';
        }
    }

    /**
     * Extracts every <loc> from a sitemap document; records malformed XML and
     * internal hostnames as errors.
     *
     * @param  list<string>  $errors
     * @return list<string>
     */
    private function locs(string $xml, string $label, array &$errors): array
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            $errors[] = $label.' is not well-formed XML.';

            return [];
        }

        $locs = [];

        foreach ($dom->getElementsByTagName('loc') as $node) {
            $loc = trim($node->textContent);

            if ($loc === '') {
                continue;
            }

            if (str_contains($loc, 'localhost') || str_contains($loc, '127.0.0.1')) {
                $errors[] = $label.' lists an internal URL: '.$loc;

                continue;
            }

            $locs[] = $loc;
        }

        return $locs;
    }

    /**
     * @param  list<string>  $errors
     */
    private function get(string $url, array &$errors, string $label): ?Response
    {
        try {
            $response = $this->client()->get($url);
        } catch (Throwable $exception) {
            $errors[] = $label.' could not be fetched: '.$exception->getMessage();

            return null;
        }

        if ($response->status() !== 200) {
            $errors[] = $label.' returned HTTP '.$response->status().'.';

            return null;
        }

        return $response;
    }

    private function client(): PendingRequest
    {
        return Http::timeout(15)
            ->withUserAgent('1HalalSeoAudit/1.0')
            ->withOptions(['allow_redirects' => ['max' => 3]]);
    }
}

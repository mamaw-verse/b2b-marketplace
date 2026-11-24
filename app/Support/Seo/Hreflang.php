<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * Cross-country `rel="alternate" hreflang` links (T3.4).
 *
 * Every country is its own deployment, so the only page with a true
 * equivalent in each is the home page. Locale is chosen by session, so one
 * URL never has per-locale alternates: only `{locale}-{COUNTRY}` pairs, one per
 * configured portal. Emitted only when this instance and at least one other
 * portal have a URL, so each deployment publishes the same reciprocal set
 * (including a self-reference); otherwise nothing is emitted.
 */
final class Hreflang
{
    /**
     * @return list<array{hreflang: string, href: string}>
     */
    public function alternates(): array
    {
        $own = (string) config('marketplace.instance_country');
        $portals = [];

        foreach ((array) config('marketplace.portals', []) as $portal) {
            $url = $this->homeUrl($portal['url'] ?? null);

            if ($url === null || blank($portal['locale'] ?? null) || blank($portal['country_code'] ?? null)) {
                continue;
            }

            $portals[strtoupper((string) $portal['country_code'])] = [
                'hreflang' => strtolower((string) $portal['locale']).'-'.strtoupper((string) $portal['country_code']),
                'href' => $url,
            ];
        }

        if (! isset($portals[strtoupper($own)]) || count($portals) < 2) {
            return [];
        }

        $links = array_values($portals);

        $default = strtoupper(trim((string) config('seo.hreflang.x_default_country')));

        if ($default !== '' && isset($portals[$default])) {
            $links[] = ['hreflang' => 'x-default', 'href' => $portals[$default]['href']];
        }

        return $links;
    }

    private function homeUrl(mixed $url): ?string
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        return rtrim($url, '/').'/';
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * The marketplace's public identity (T5.6): one place that owns the name, the
 * one-sentence description, the external profiles and the support email.
 *
 * Organization and WebSite JSON-LD, /llms.txt and the web manifest all read it, so
 * the entity is described the same way everywhere. The same wording should be used
 * on the social profiles listed in `seo.social_profiles`; set it once with
 * SEO_ENTITY_DESCRIPTION and paste it into each profile's bio.
 */
final class EntityProfile
{
    public function name(): string
    {
        return (string) config('seo.site_name');
    }

    /**
     * The configured description, or the translated default sentence.
     */
    public function description(): string
    {
        $configured = config('seo.entity_description');

        if (is_string($configured) && trim($configured) !== '') {
            return trim((string) preg_replace('/\s+/', ' ', $configured));
        }

        return __(':app is a B2B marketplace that connects buyers with verified halal-certified suppliers, manufacturers and products across the halal supply chain.', ['app' => $this->name()]);
    }

    /**
     * Public profile URLs for `sameAs`: absolute http(s) URLs only, without
     * duplicates, in configured order.
     *
     * @return list<string>
     */
    public function sameAs(): array
    {
        $urls = [];

        foreach ((array) config('seo.social_profiles', []) as $url) {
            if (! is_string($url)) {
                continue;
            }

            $url = trim($url);

            if (filter_var($url, FILTER_VALIDATE_URL) === false || ! preg_match('#^https?://#i', $url)) {
                continue;
            }

            $urls[$url] = $url;
        }

        return array_values($urls);
    }

    /**
     * The public support email, or null when none is configured (or it is invalid).
     */
    public function contactEmail(): ?string
    {
        $email = config('seo.contact.email');

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }
}

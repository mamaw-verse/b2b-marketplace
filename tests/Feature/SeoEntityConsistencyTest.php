<?php

declare(strict_types=1);

use App\Support\Seo\EntityProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('marketplace.features.subscriptions_enabled', false);
    Config::set('app.url', 'https://seo.example.test');
});

/**
 * The first node of a type in the page's single JSON-LD document.
 *
 * @return array<string, mixed>
 */
function entityNode(string $html, string $type): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    expect($matches[1])->toHaveCount(1);

    $graph = json_decode($matches[1][0], true, 512, JSON_THROW_ON_ERROR)['@graph'];
    $nodes = array_values(array_filter($graph, static fn (array $node): bool => $node['@type'] === $type));

    expect($nodes)->not->toBeEmpty();

    return $nodes[0];
}

// ─── EntityProfile ─────────────────────────────────────────────────────

test('the entity description defaults to the translated sentence naming the site', function () {
    Config::set('seo.site_name', 'Acme Halal');
    Config::set('seo.entity_description', null);

    expect(app(EntityProfile::class)->description())
        ->toStartWith('Acme Halal is a B2B marketplace')
        ->and(app(EntityProfile::class)->name())->toBe('Acme Halal');
});

test('a configured entity description replaces the default and is collapsed to one line', function () {
    Config::set('seo.entity_description', "  Halal sourcing,\n  verified.  ");

    expect(app(EntityProfile::class)->description())->toBe('Halal sourcing, verified.');
});

test('a blank configured description falls back to the default', function () {
    Config::set('seo.entity_description', '   ');

    expect(app(EntityProfile::class)->description())->toContain('B2B marketplace');
});

test('sameAs keeps only absolute http(s) URLs, trimmed and without duplicates', function () {
    Config::set('seo.social_profiles', [
        ' https://www.facebook.com/example ',
        'https://www.facebook.com/example',
        'http://example.test/page',
        'javascript://example.test/%0Aalert(1)',
        'ftp://example.test/file',
        'not a url',
        '',
        42,
        'https://www.linkedin.com/company/example',
    ]);

    expect(app(EntityProfile::class)->sameAs())->toBe([
        'https://www.facebook.com/example',
        'http://example.test/page',
        'https://www.linkedin.com/company/example',
    ]);
});

test('the support email is returned only when it is a valid address', function () {
    Config::set('seo.contact.email', 'help@example.test');
    expect(app(EntityProfile::class)->contactEmail())->toBe('help@example.test');

    Config::set('seo.contact.email', 'not-an-email');
    expect(app(EntityProfile::class)->contactEmail())->toBeNull();

    Config::set('seo.contact.email', null);
    expect(app(EntityProfile::class)->contactEmail())->toBeNull();
});

// ─── One identity everywhere ───────────────────────────────────────────

test('organization, website, llms.txt and the manifest describe the entity identically', function (?string $override) {
    app()->detectEnvironment(fn (): string => 'production');
    Config::set('seo.force_noindex', false);
    Config::set('seo.site_name', 'Acme Halal');
    Config::set('seo.entity_description', $override);

    $home = $this->get('/')->assertOk()->getContent();
    $organization = entityNode($home, 'Organization');
    $website = entityNode($home, 'WebSite');
    $manifest = $this->get('/manifest.webmanifest')->assertOk()->json();
    $llms = $this->get('/llms.txt')->assertOk()->getContent();

    $name = app(EntityProfile::class)->name();
    $description = app(EntityProfile::class)->description();

    expect($organization['name'])->toBe($name)
        ->and($website['name'])->toBe($name)
        ->and($manifest['name'])->toBe($name)
        ->and($organization['description'])->toBe($description)
        ->and($website['description'])->toBe($description)
        ->and($manifest['description'])->toBe($description)
        ->and($llms)->toStartWith('# '.$name."\n\n> ".$description."\n");
})->with([
    'default description' => [null],
    'configured description' => ['The halal B2B marketplace for verified suppliers.'],
]);

test('the organization sameAs and contactPoint come from the shared profile', function () {
    Config::set('seo.social_profiles', ['https://www.facebook.com/example', 'javascript://x/%0Aalert(1)']);
    Config::set('seo.contact.email', 'help@example.test');

    $organization = entityNode($this->get('/')->assertOk()->getContent(), 'Organization');

    expect($organization['sameAs'])->toBe(['https://www.facebook.com/example'])
        ->and($organization['contactPoint']['email'])->toBe('help@example.test');
});

// ─── Configuration guardrails ──────────────────────────────────────────

test('config/seo.php defines every top-level key once', function () {
    preg_match_all("/^    '([a-z0-9_]+)' => /m", (string) file_get_contents(config_path('seo.php')), $matches);

    $duplicates = array_keys(array_filter(array_count_values($matches[1]), static fn (int $count): bool => $count > 1));

    expect($duplicates)->toBe([]);
});

test('the AI crawler lists are populated', function () {
    expect(config('seo.ai_crawlers.search'))->not->toBeEmpty()
        ->and(config('seo.ai_crawlers.training'))->not->toBeEmpty()
        ->and(config('seo.ai_crawlers.grounding'))->not->toBeEmpty();
});

test('every SEO_ environment variable read by config/seo.php is documented in .env.example', function () {
    preg_match_all("/env\('(SEO_[A-Z0-9_]+)'/", (string) file_get_contents(config_path('seo.php')), $used);
    preg_match_all('/^(SEO_[A-Z0-9_]+)=/m', (string) file_get_contents(base_path('.env.example')), $documented);

    expect($used[1])->not->toBeEmpty()
        ->and(array_values(array_diff(array_unique($used[1]), $documented[1])))->toBe([]);
});

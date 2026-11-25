<?php

declare(strict_types=1);

use App\Actions\Seo\AuditSeo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

const AUDIT_ORIGIN = 'https://audit.example.test';

function auditPrivateDisallows(): string
{
    return implode("\n", array_map(
        static fn (string $pattern): string => 'Disallow: /'.$pattern,
        array_values(array_unique(array_map(static fn (mixed $p): string => trim((string) $p, '/'), config('seo.private_path_patterns')))),
    ));
}

function auditJpeg(int $width = 1200, int $height = 630): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

function auditPage(string $canonical = AUDIT_ORIGIN.'/about', bool $valid = true): string
{
    $graph = $valid
        ? '{"@context":"https://schema.org","@graph":[{"@type":"WebPage","@id":"'.AUDIT_ORIGIN.'/about#webpage","url":"'.AUDIT_ORIGIN.'/about"}]}'
        : '{"@context":"https://schema.org","@graph":[{"url":"'.AUDIT_ORIGIN.'/about"}]}';

    return '<!doctype html><html><head><title>About</title>'
        .'<meta name="description" content="About us">'
        .'<link rel="canonical" href="'.$canonical.'">'
        .'<meta property="og:image" content="'.AUDIT_ORIGIN.'/og-default.jpg">'
        .'<meta name="twitter:image" content="'.AUDIT_ORIGIN.'/og-default.jpg">'
        .'<script type="application/ld+json">'.$graph.'</script>'
        .'</head><body><h1>About</h1></body></html>';
}

function fakeHealthySite(bool $validPage = true, string $canonical = AUDIT_ORIGIN.'/about'): void
{
    Http::fake([
        AUDIT_ORIGIN.'/robots.txt' => Http::response("User-agent: *\n".auditPrivateDisallows()."\n\nSitemap: ".AUDIT_ORIGIN."/sitemap.xml\n"),
        AUDIT_ORIGIN.'/llms.txt' => Http::response("# 1Halal Store\n\n> Marketplace.\n"),
        AUDIT_ORIGIN.'/sitemap.xml' => Http::response('<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>'.AUDIT_ORIGIN.'/sitemap-pages.xml</loc></sitemap></sitemapindex>'),
        AUDIT_ORIGIN.'/sitemap-pages.xml' => Http::response('<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>'.AUDIT_ORIGIN.'/about</loc></url></urlset>'),
        AUDIT_ORIGIN.'/og-default.jpg' => Http::response(auditJpeg()),
        AUDIT_ORIGIN.'/*' => Http::response(auditPage($canonical, $validPage)),
    ]);
}

beforeEach(function () {
    Config::set('seo.sitemap_enabled', true);
    Config::set('seo.force_noindex', false);
    Config::set('seo.sitemap.static_routes', ['about']);
    app()->detectEnvironment(fn (): string => 'production');
});

it('passes on a healthy site', function () {
    fakeHealthySite();

    $result = app(AuditSeo::class)->handle(AUDIT_ORIGIN, 3);

    expect($result['errors'])->toBe([])
        ->and($result['checked'])->toBeGreaterThan(4);
});

it('flags a blanket robots block, a missing private disallow and a missing sitemap line', function () {
    Http::fake([
        AUDIT_ORIGIN.'/robots.txt' => Http::response("User-agent: *\nDisallow: /\n"),
        AUDIT_ORIGIN.'/llms.txt' => Http::response("# x\n"),
        AUDIT_ORIGIN.'/sitemap.xml' => Http::response('', 404),
    ]);
    Config::set('seo.sitemap.static_routes', []);

    $errors = app(AuditSeo::class)->handle(AUDIT_ORIGIN)['errors'];

    expect(implode("\n", $errors))
        ->toContain('blocks the whole site')
        ->toContain('no Sitemap: line')
        ->toContain('does not disallow /admin')
        ->toContain('sitemap.xml returned HTTP 404');
});

it('flags invalid JSON-LD on a sampled page', function () {
    fakeHealthySite(validPage: false);

    $errors = app(AuditSeo::class)->handle(AUDIT_ORIGIN)['errors'];

    expect(implode("\n", $errors))->toContain('JSON-LD');
});

it('flags a canonical with a query string', function () {
    fakeHealthySite(canonical: AUDIT_ORIGIN.'/about?utm_source=x');

    $errors = app(AuditSeo::class)->handle(AUDIT_ORIGIN)['errors'];

    expect(implode("\n", $errors))->toContain('canonical must be an absolute, query-free URL');
});

it('flags a sitemap that lists an internal host and a broken image', function () {
    Http::fake([
        AUDIT_ORIGIN.'/robots.txt' => Http::response("User-agent: *\n".auditPrivateDisallows()."\n\nSitemap: ".AUDIT_ORIGIN."/sitemap.xml\n"),
        AUDIT_ORIGIN.'/llms.txt' => Http::response("# x\n"),
        AUDIT_ORIGIN.'/sitemap.xml' => Http::response('<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>http://localhost/sitemap-pages.xml</loc></sitemap></sitemapindex>'),
        AUDIT_ORIGIN.'/og-default.jpg' => Http::response('', 404),
        AUDIT_ORIGIN.'/*' => Http::response(auditPage()),
    ]);

    $errors = implode("\n", app(AuditSeo::class)->handle(AUDIT_ORIGIN)['errors']);

    expect($errors)->toContain('lists an internal URL')->toContain('returned HTTP 404');
});

it('flags a social image that is too small', function () {
    Http::fake([
        AUDIT_ORIGIN.'/robots.txt' => Http::response("User-agent: *\n".auditPrivateDisallows()."\n\nSitemap: ".AUDIT_ORIGIN."/sitemap.xml\n"),
        AUDIT_ORIGIN.'/llms.txt' => Http::response("# x\n"),
        AUDIT_ORIGIN.'/sitemap.xml' => Http::response('<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'),
        AUDIT_ORIGIN.'/og-default.jpg' => Http::response(auditJpeg(300, 200)),
        AUDIT_ORIGIN.'/*' => Http::response(auditPage()),
    ]);

    $errors = implode("\n", app(AuditSeo::class)->handle(AUDIT_ORIGIN)['errors']);

    expect($errors)->toContain('300x200');
});

it('flags a social image that is not a readable image', function () {
    Http::fake([
        AUDIT_ORIGIN.'/robots.txt' => Http::response("User-agent: *\n".auditPrivateDisallows()."\n\nSitemap: ".AUDIT_ORIGIN."/sitemap.xml\n"),
        AUDIT_ORIGIN.'/llms.txt' => Http::response("# x\n"),
        AUDIT_ORIGIN.'/sitemap.xml' => Http::response('<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'),
        AUDIT_ORIGIN.'/og-default.jpg' => Http::response('<html>not an image</html>'),
        AUDIT_ORIGIN.'/*' => Http::response(auditPage()),
    ]);

    $errors = implode("\n", app(AuditSeo::class)->handle(AUDIT_ORIGIN)['errors']);

    expect($errors)->toContain('not a readable raster image');
});

it('refuses to audit a noindex deployment unless allowed', function () {
    app()->detectEnvironment(fn (): string => 'staging');

    $this->artisan('seo:audit')->assertFailed();
});

it('exits zero when the audit finds nothing', function () {
    Config::set('app.url', AUDIT_ORIGIN);
    fakeHealthySite();

    $this->artisan('seo:audit', ['--sample' => 2])->assertSuccessful();
});

it('exits non-zero when the audit finds a problem', function () {
    Config::set('app.url', AUDIT_ORIGIN);
    fakeHealthySite(validPage: false);

    $this->artisan('seo:audit', ['--sample' => 2])->assertFailed();
});

it('is scheduled weekly on Mondays at 05:00 UTC, in production only', function () {
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'seo:audit'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 5 * * 1')
        ->and($event->timezone)->toBe('UTC')
        ->and($event->environments)->toBe(['production']);
});

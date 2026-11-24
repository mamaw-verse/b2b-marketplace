<?php

declare(strict_types=1);

use App\Support\Seo\IndexingPolicy;
use Illuminate\Support\Facades\Config;

function seoAsProduction(): void
{
    app()->detectEnvironment(fn (): string => 'production');
    Config::set('seo.force_noindex', false);
}

test('robots.txt is served dynamically and no static SEO files exist in public', function () {
    seoAsProduction();

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect(glob(public_path('{robots.txt,sitemap*.xml,llms*.txt}'), GLOB_BRACE))->toBeEmpty();
});

test('production robots.txt disallows every configured private path', function () {
    seoAsProduction();

    $body = $this->get('/robots.txt')->assertOk()->getContent();

    // The wildcard group must never be a blanket block (AI training bots have their own groups).
    expect($body)->toContain('User-agent: *')
        ->and(seoRobotsGroups($body)['*'])->not->toContain('/');

    foreach (app(IndexingPolicy::class)->privatePathPatterns() as $pattern) {
        expect($body)->toContain('Disallow: /'.$pattern."\n");
    }
});

test('robots.txt only advertises the sitemap when it is enabled', function () {
    seoAsProduction();

    Config::set('seo.sitemap_enabled', false);
    $this->get('/robots.txt')->assertDontSee('Sitemap:');

    Config::set('seo.sitemap_enabled', true);
    $this->get('/robots.txt')->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

test('non-production environments disallow everything', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /', false);

    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");
});

test('SEO_FORCE_NOINDEX disallows everything even in production', function () {
    seoAsProduction();
    Config::set('seo.force_noindex', true);

    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");
    $this->get('/about')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('private areas send a noindex X-Robots-Tag header in production', function (string $path) {
    seoAsProduction();

    $this->get($path)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with(['/admin', '/hub', '/account/profile', '/portal/dashboard', '/settings/profile', '/staff/invitations/some-token']);

test('public pages carry no X-Robots-Tag in production', function (string $path) {
    seoAsProduction();

    $this->get($path)->assertHeaderMissing('X-Robots-Tag');
})->with(['/', '/about', '/faq', '/companies', '/products']);

test('every public page is noindex outside production', function () {
    $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('robots.txt, the header and the policy share the same private path list', function () {
    $policy = app(IndexingPolicy::class);

    foreach ($policy->privatePathPatterns() as $pattern) {
        expect($policy->isPrivatePath(Illuminate\Http\Request::create('/'.$pattern)))->toBeTrue()
            ->and($policy->isPrivatePath(Illuminate\Http\Request::create('/'.$pattern.'/child')))->toBeTrue();
    }

    expect($policy->isPrivatePath(Illuminate\Http\Request::create('/products')))->toBeFalse()
        ->and($policy->isPrivatePath(Illuminate\Http\Request::create('/companies/acme')))->toBeFalse();
});

test('brand image assets exist with the expected dimensions', function () {
    [$ogWidth, $ogHeight] = getimagesize(public_path(config('seo.default_og_image')));
    [$logoWidth, $logoHeight] = getimagesize(public_path(config('seo.logo_path')));

    expect([$ogWidth, $ogHeight])->toBe([1200, 630])
        ->and($logoWidth)->toBe($logoHeight)
        ->and($logoWidth)->toBeGreaterThanOrEqual(112);

    foreach (['favicon.ico', 'favicon-32.png', 'icon-192.png', 'apple-touch-icon.png'] as $icon) {
        expect(public_path($icon))->toBeFile();
    }
});

test('layouts reference only icon files that exist', function () {
    $layouts = [
        'partials/head', 'layouts/storefront', 'layouts/portal', 'errors/layout',
    ];

    foreach ($layouts as $layout) {
        $contents = file_get_contents(resource_path("views/{$layout}.blade.php"));

        expect($contents)->not->toContain('/favicon.svg');

        preg_match_all('#<link rel="(?:icon|apple-touch-icon)" href="(/[^"]+)"#', $contents, $matches);

        foreach ($matches[1] as $href) {
            expect(public_path(ltrim($href, '/')))->toBeFile();
        }
    }
});

/**
 * Parses robots.txt into [agent => list of Disallow paths], one entry per User-agent line.
 *
 * @return array<string, list<string>>
 */
function seoRobotsGroups(string $body): array
{
    $groups = [];

    foreach (preg_split('/\n\s*\n/', trim($body)) as $block) {
        $agents = [];
        $disallows = [];

        foreach (explode("\n", $block) as $line) {
            if (str_starts_with($line, 'User-agent: ')) {
                $agents[] = substr($line, 12);
            } elseif (str_starts_with($line, 'Disallow: ')) {
                $disallows[] = substr($line, 10);
            }
        }

        foreach ($agents as $agent) {
            $groups[$agent] = $disallows;
        }
    }

    return $groups;
}

test('every named AI crawler group repeats all private disallows', function () {
    seoAsProduction();
    Config::set('seo.block_ai_training', false);

    $groups = seoRobotsGroups($this->get('/robots.txt')->assertOk()->getContent());
    $private = array_map(
        static fn (string $pattern): string => '/'.$pattern,
        app(IndexingPolicy::class)->privatePathPatterns(),
    );
    $named = [...config('seo.ai_crawlers.search'), ...config('seo.ai_crawlers.training')];

    expect($named)->not->toBeEmpty();

    foreach ($named as $agent) {
        expect($groups)->toHaveKey($agent)
            ->and($groups[$agent])->toBe($private);
    }
});

test('the training toggle changes only the training groups', function () {
    seoAsProduction();

    Config::set('seo.block_ai_training', false);
    $open = seoRobotsGroups($this->get('/robots.txt')->getContent());

    Config::set('seo.block_ai_training', true);
    $blocked = seoRobotsGroups($this->get('/robots.txt')->getContent());

    $training = config('seo.ai_crawlers.training');

    foreach ($training as $agent) {
        expect($blocked[$agent])->toBe(['/'])->and($open[$agent])->not->toBe(['/']);
    }

    expect(array_diff_key($blocked, array_flip($training)))->toBe(array_diff_key($open, array_flip($training)));
});

test('answer and user-triggered bots stay allowed even when training bots are blocked', function () {
    seoAsProduction();
    Config::set('seo.block_ai_training', true);

    $groups = seoRobotsGroups($this->get('/robots.txt')->getContent());

    foreach (config('seo.ai_crawlers.search') as $agent) {
        expect($groups[$agent])->not->toContain('/');
    }
});

test('a non-production deploy blocks every crawler including AI bots', function () {
    Config::set('seo.force_noindex', true);

    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");
});

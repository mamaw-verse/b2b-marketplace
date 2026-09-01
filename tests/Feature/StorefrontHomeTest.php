<?php

declare(strict_types=1);

test('home page loads successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('hero section renders MHCB-Verified badge', function () {
    $response = $this->get(route('home'));

    $response->assertSee('MHCB-Verified Halal Marketplace');
});

test('hero section renders split-layout headline', function () {
    $response = $this->get(route('home'));

    $response->assertSee('We connect the entire halal supply chain');
    $response->assertSee('not just a storefront');
});

test('hero section renders CTA buttons', function () {
    $response = $this->get(route('home'));

    $response->assertSee('Join the Movement');
    $response->assertSee('See How It Works');
});

test('hero section renders trust stats', function () {
    $response = $this->get(route('home'));

    $response->assertSee('Verified Companies');
    $response->assertSee('Halal Products');
    $response->assertSee('Categories');
});

test('hero section renders MHCB certification floating card', function () {
    $response = $this->get(route('home'));

    $response->assertSee('MHCB Certified');
    $response->assertSee('Philippines');
});

test('hero section renders Halal Business Guru floating card', function () {
    $response = $this->get(route('home'));

    $response->assertSee('Ismail Abaya, PhD');
    $response->assertSee('Halal Business Guru');
});

test('homepage sections have smooth-scroll anchor IDs', function () {
    $response = $this->get(route('home'));

    $response->assertSee('id="who-we-serve"', false);
    $response->assertSee('id="what-we-offer"', false);
    $response->assertSee('id="supply-chain"', false);
    $response->assertSee('id="join"', false);
});

test('navigation header contains anchor links for homepage sections', function () {
    $response = $this->get(route('home'));

    $response->assertSee('Who We Serve');
    $response->assertSee('What We Offer');
    $response->assertSee('Supply Chain');
    $response->assertSee('Join Us');
});

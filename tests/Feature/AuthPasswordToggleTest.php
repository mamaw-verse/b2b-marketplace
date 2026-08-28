<?php

declare(strict_types=1);

test('login page renders self-contained password toggle without flux viewable', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    // Self-contained Alpine toggle present
    $response->assertSee('x-data="{ show: false }"', false);
    $response->assertSee(':type="show ? \'text\' : \'password\'"', false);
    $response->assertSee('@click="show = !show"', false);
    // The broken Flux viewable attribute must be gone from the password field
    $response->assertDontSee('viewable', false);
});

test('register page renders self-contained password toggles for both fields', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('x-data="{ show: false }"', false);
    $response->assertSee('name="password"', false);
    $response->assertSee('name="password_confirmation"', false);
    $response->assertDontSee('viewable', false);
});

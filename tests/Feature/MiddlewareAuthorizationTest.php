<?php

declare(strict_types=1);

use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\RolePermissionSeeder::class);
});

test('unauthenticated request to hub redirects to login', function () {
    $response = $this->get('/hub');

    $response->assertRedirect(route('login'));
});

test('unauthenticated request to admin redirects to login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect(route('login'));
});

test('authenticated non-admin request to admin returns 403', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller)->get('/admin');

    $response->assertForbidden();
});

test('authenticated admin can access admin routes', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');

    // /admin now redirects to /admin/onboardings
    $response->assertRedirect('/admin/onboardings');
});

test('authenticated verified user can access hub routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/hub');

    // /hub now redirects to the appropriate hub page
    $response->assertRedirect();
});

test('unverified user cannot access hub routes', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/hub');

    $response->assertRedirect(route('verification.notice'));
});

<?php

declare(strict_types=1);

use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\RolePermissionSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get('/redirect');
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected to hub onboarding', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/redirect');
    $response->assertRedirect(route('hub.onboarding'));
});

test('admin users are redirected to admin console', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $response = $this->get('/redirect');
    $response->assertRedirect(route('admin.onboardings.index'));
});

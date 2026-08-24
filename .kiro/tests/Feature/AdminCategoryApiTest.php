<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('non-admin cannot access admin categories and gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/admin/categories');

    $response->assertForbidden();
});

test('admin can list all categories', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    Category::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/categories');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('creating Bakery Ingredients produces slug bakery-ingredients', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/categories', [
        'name' => 'Bakery Ingredients',
        'description' => 'All bakery related ingredients',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Bakery Ingredients')
        ->assertJsonPath('data.slug', 'bakery-ingredients');

    $this->assertDatabaseHas('categories', [
        'name' => 'Bakery Ingredients',
        'slug' => 'bakery-ingredients',
    ]);
});

test('duplicate category name returns 422 with VALIDATION_ERROR envelope', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    Category::factory()->create(['name' => 'Existing Category', 'slug' => 'existing-category']);

    $response = $this->postJson('/api/v1/admin/categories', [
        'name' => 'Existing Category',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['code', 'message', 'fields']]);
});

test('admin can update a category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $response = $this->putJson("/api/v1/admin/categories/{$category->id}", [
        'name' => 'New Name',
        'description' => 'Updated description',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.slug', 'new-name')
        ->assertJsonPath('data.description', 'Updated description');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'New Name',
        'slug' => 'new-name',
    ]);
});

test('admin can delete a category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $category = Category::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

    $response->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

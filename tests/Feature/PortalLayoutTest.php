<?php

declare(strict_types=1);

use App\Models\BuyerProfile;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders hub sidebar for sellers/company accounts even on account pages', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    actingAs($seller);

    $response = $this->get(route('account.profile'));

    $response->assertOk();
    // Check that it shows "Company Hub" header / elements in the sidebar
    $response->assertSee('Company Hub');
    // Check that it sees Company Hub links like Products and RFQ Queue
    $response->assertSee('Products');
    $response->assertSee('RFQ Queue');
});

it('renders account sidebar for buyer-only accounts on account pages', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');
    BuyerProfile::factory()->create(['user_id' => $buyer->id]);

    actingAs($buyer);

    $response = $this->get(route('account.profile'));

    $response->assertOk();
    // Check that it shows "My Account" header
    $response->assertSee('My Account');
    // Check that it does NOT see Company Hub specific links like Products or RFQ Queue
    $response->assertDontSee('RFQ Queue');
});

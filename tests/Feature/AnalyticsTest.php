<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\PayoutSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(PayoutSettingsSeeder::class);
});

test('admin analytics returns correct GMV for given window', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $shop = Shop::factory()->create();

    // Order within window
    Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 10000.00,
        'paid_at' => now()->subDays(5),
    ]);

    // Another completed order within window
    Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 5000.00,
        'paid_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/analytics?from='.now()->subDays(30)->format('Y-m-d').'&to='.now()->format('Y-m-d'));

    $response->assertOk()
        ->assertJsonPath('data.gmv', 15000);
});

test('admin analytics returns correct total_orders count', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $shop = Shop::factory()->create();

    Order::factory()->completed()->count(3)->create([
        'shop_id' => $shop->id,
        'paid_at' => now()->subDays(2),
    ]);

    // Draft order should not count
    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/analytics?from='.now()->subDays(30)->format('Y-m-d').'&to='.now()->format('Y-m-d'));

    $response->assertOk()
        ->assertJsonPath('data.total_orders', 3);
});

test('platform average rating computed correctly excludes shops with 0 reviews', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Shop with reviews
    Shop::factory()->create(['average_rating' => 4.50, 'total_reviews' => 10]);
    Shop::factory()->create(['average_rating' => 3.50, 'total_reviews' => 5]);

    // Shop with 0 reviews should be excluded
    Shop::factory()->create(['average_rating' => 0.00, 'total_reviews' => 0]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/analytics');

    $response->assertOk();

    // Average of 4.50 and 3.50 = 4.00
    expect((float) $response->json('data.platform_average_rating'))->toBe(4.0);
});

test('date range filtering works and orders outside window are excluded', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $shop = Shop::factory()->create();

    // Order inside window
    Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 5000.00,
        'paid_at' => now()->subDays(5),
    ]);

    // Order outside window (60 days ago)
    Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 8000.00,
        'paid_at' => now()->subDays(60),
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/analytics?from='.now()->subDays(30)->format('Y-m-d').'&to='.now()->format('Y-m-d'));

    $response->assertOk()
        ->assertJsonPath('data.gmv', 5000)
        ->assertJsonPath('data.total_orders', 1);
});

test('non-admin cannot access analytics endpoint', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/analytics');

    $response->assertForbidden();
});

test('seller reports include review averages', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'average_rating' => 4.25,
        'total_reviews' => 12,
    ]);

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/reports');

    $response->assertOk()
        ->assertJsonPath('data.average_rating', 4.25)
        ->assertJsonPath('data.total_reviews', 12);
});

test('seller reports include pending_balance from ledger entries', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $shop = Shop::factory()->create(['user_id' => $seller->id]);

    $order = Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 10000.00,
        'paid_at' => now()->subDays(3),
    ]);

    // Create escrow_hold ledger entry for this completed order
    DB::table('ledger_entries')->insert([
        'order_id' => $order->id,
        'type' => 'escrow_hold',
        'amount' => 9700.00,
        'note' => 'Escrow hold for order',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($seller, 'sanctum')
        ->getJson('/api/v1/hub/reports');

    $response->assertOk()
        ->assertJsonPath('data.pending_balance', 9700);
});

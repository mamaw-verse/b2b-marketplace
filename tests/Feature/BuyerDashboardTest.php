<?php

declare(strict_types=1);

use App\Livewire\Account\Dashboard;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');
});

test('the buyer dashboard renders status cards', function () {
    Livewire::actingAs($this->buyer)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('To Pay')
        ->assertSee('To Ship')
        ->assertSee('To Receive')
        ->assertSee('Completed');
});

test('status counts reflect the buyer orders', function () {
    $shop = Shop::factory()->create(['status' => 'active']);

    Order::factory()->count(2)->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $shop->id,
        'status' => 'pending_payment',
    ]);
    Order::factory()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $shop->id,
        'status' => 'completed',
    ]);

    $component = Livewire::actingAs($this->buyer)->test(Dashboard::class);

    expect($component->instance()->statusCounts()['to_pay'])->toBe(2)
        ->and($component->instance()->statusCounts()['completed'])->toBe(1);
});

test('shows Register Business CTA when the user has no shop', function () {
    Livewire::actingAs($this->buyer)
        ->test(Dashboard::class)
        ->assertSee('Register Business');
});

test('hides Register Business CTA when the user owns a shop', function () {
    Shop::factory()->create(['user_id' => $this->buyer->id, 'status' => 'active']);

    Livewire::actingAs($this->buyer)
        ->test(Dashboard::class)
        ->assertDontSee('Register Business');
});

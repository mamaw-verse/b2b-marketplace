<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\Orders\OrderQueue;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create(['email_verified_at' => now()]);
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('tabCounts reflects the number of orders per status', function () {
    Order::factory()->count(2)->create(['shop_id' => $this->shop->id, 'status' => 'draft']);
    Order::factory()->create(['shop_id' => $this->shop->id, 'status' => 'pending_payment']);
    Order::factory()->count(3)->create(['shop_id' => $this->shop->id, 'status' => 'paid']);

    $counts = Livewire::actingAs($this->seller)
        ->test(OrderQueue::class)
        ->instance()
        ->tabCounts();

    expect($counts['all'])->toBe(6)
        ->and($counts['draft'])->toBe(2)
        ->and($counts['pending_payment'])->toBe(1)
        ->and($counts['to_fulfil'])->toBe(3)
        ->and($counts['disputed'])->toBe(0);
});

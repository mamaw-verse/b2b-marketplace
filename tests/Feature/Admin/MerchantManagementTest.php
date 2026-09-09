<?php

declare(strict_types=1);

use App\Jobs\IndexShopJob;
use App\Livewire\Admin\Merchants\MerchantList;
use App\Livewire\Admin\Merchants\MerchantShow;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('can list merchants', function () {
    Shop::factory()->count(3)->create(['status' => 'active']);

    Livewire::actingAs($this->admin)
        ->test(MerchantList::class)
        ->assertOk()
        ->assertSee('Merchants')
        ->assertViewHas('merchants', function ($merchants) {
            return $merchants->count() === 3;
        });
});

it('can suspend and reactivate a merchant', function () {
    Queue::fake();

    $shop = Shop::factory()->create(['status' => 'active']);

    Livewire::actingAs($this->admin)
        ->test(MerchantShow::class, ['shop' => $shop])
        ->assertOk()
        ->call('suspend')
        ->assertDispatched('toast');

    expect($shop->fresh()->status)->toBe('suspended');

    Queue::assertPushed(IndexShopJob::class, fn (IndexShopJob $job) => $job->shop->id === $shop->id);

    Livewire::actingAs($this->admin)
        ->test(MerchantShow::class, ['shop' => $shop])
        ->call('reactivate')
        ->assertDispatched('toast');

    expect($shop->fresh()->status)->toBe('active');
});

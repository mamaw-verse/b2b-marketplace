<?php

declare(strict_types=1);

use App\Livewire\Admin\Merchants\MerchantList;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

describe('is_halal_certified accessor', function () {
    it('is true when a shop has an active certificate', function () {
        $shop = Shop::factory()->create();
        ShopCertificate::factory()->for($shop)->create(['status' => 'active']);

        expect($shop->refresh()->is_halal_certified)->toBeTrue();
    });

    it('is true when a shop has an expiring_soon certificate', function () {
        $shop = Shop::factory()->create();
        ShopCertificate::factory()->expiringSoon()->for($shop)->create();

        expect($shop->refresh()->is_halal_certified)->toBeTrue();
    });

    it('is false when a shop only has expired certificates', function () {
        $shop = Shop::factory()->create();
        ShopCertificate::factory()->expired()->for($shop)->create();

        expect($shop->refresh()->is_halal_certified)->toBeFalse();
    });

    it('is false when a shop has no certificates', function () {
        $shop = Shop::factory()->create();

        expect($shop->refresh()->is_halal_certified)->toBeFalse();
    });

    it('is true when a shop has both expired and active certificates', function () {
        $shop = Shop::factory()->create();
        ShopCertificate::factory()->expired()->for($shop)->create();
        ShopCertificate::factory()->for($shop)->create(['status' => 'active']);

        expect($shop->refresh()->is_halal_certified)->toBeTrue();
    });
});

describe('certification filter in MerchantList', function () {
    it('filters certified merchants — only those with active or expiring_soon certs', function () {
        $certifiedShop = Shop::factory()->create();
        ShopCertificate::factory()->for($certifiedShop)->create(['status' => 'active']);

        $expiringSoonShop = Shop::factory()->create();
        ShopCertificate::factory()->expiringSoon()->for($expiringSoonShop)->create();

        $expiredShop = Shop::factory()->create();
        ShopCertificate::factory()->expired()->for($expiredShop)->create();

        $noCertShop = Shop::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(MerchantList::class)
            ->set('certificationFilter', 'certified')
            ->assertSee($certifiedShop->storefront_name)
            ->assertSee($expiringSoonShop->storefront_name)
            ->assertDontSee($expiredShop->storefront_name)
            ->assertDontSee($noCertShop->storefront_name);
    });

    it('filters not_certified merchants — those with only expired certs or none at all', function () {
        $certifiedShop = Shop::factory()->create();
        ShopCertificate::factory()->for($certifiedShop)->create(['status' => 'active']);

        $expiredShop = Shop::factory()->create();
        ShopCertificate::factory()->expired()->for($expiredShop)->create();

        $noCertShop = Shop::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(MerchantList::class)
            ->set('certificationFilter', 'not_certified')
            ->assertDontSee($certifiedShop->storefront_name)
            ->assertSee($expiredShop->storefront_name)
            ->assertSee($noCertShop->storefront_name);
    });
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows current instance portal with "you are here" and others from config', function () {
    config(['marketplace.instance_country' => 'PH']);
    config(['marketplace.portals' => [
        ['country_code' => 'PH', 'display_name' => 'Philippines', 'flag' => '🇵🇭', 'locale' => 'en', 'url' => null],
        ['country_code' => 'MY', 'display_name' => 'Malaysia',    'flag' => '🇲🇾', 'locale' => 'ms', 'url' => 'https://my.1halalstore.com'],
    ]]);

    $this->get('/')
        ->assertSee('You are here')
        ->assertSee('Malaysia')
        ->assertSee('https://my.1halalstore.com');
});

it('persists locale switch and redirects', function () {
    $this->post(route('locale.switch'), ['locale' => 'ms'])
        ->assertSessionHas('locale', 'ms')
        ->assertRedirect();
});

it('rejects invalid locale codes', function () {
    $this->post(route('locale.switch'), ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('falls back to instance country default locale when no session locale is set', function () {
    config(['marketplace.instance_country' => 'MY']);
    config(['marketplace.portals' => [
        ['country_code' => 'PH', 'display_name' => 'Philippines', 'flag' => '🇵🇭', 'locale' => 'en', 'url' => null],
        ['country_code' => 'MY', 'display_name' => 'Malaysia',    'flag' => '🇲🇾', 'locale' => 'ms', 'url' => null],
    ]]);

    $this->get('/')->assertStatus(200);

    expect(app()->getLocale())->toBe('ms');
});

it('scopes company directory to instance country', function () {
    config(['marketplace.instance_country' => 'MY']);

    App\Models\Shop::factory()->create(['status' => 'active', 'country_code' => 'MY']);
    App\Models\Shop::factory()->create(['status' => 'active', 'country_code' => 'PH']);

    $component = Livewire\Livewire::test(App\Livewire\Storefront\CompanyDirectory::class);

    expect($component->instance()->shops()->total())->toBe(1);
});

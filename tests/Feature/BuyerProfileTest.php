<?php

declare(strict_types=1);

use App\Livewire\Account\BuyerProfileForm;
use App\Models\BuyerProfile;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Buyer Profile API', function () {
    test('authenticated user can create buyer profile via PUT', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/buyer-profile', [
            'business_name' => 'Manila Bakery Supplies Inc.',
            'billing_address_1' => 'Unit 5, Golden Tower',
            'billing_city' => 'Makati',
            'billing_province' => 'Metro Manila',
            'billing_zip_code' => '1226',
            'billing_country_code' => 'PH',
            'shipping_address_1' => 'Warehouse 3, Block 7',
            'shipping_city' => 'Biñan',
            'shipping_province' => 'Laguna',
            'shipping_zip_code' => '4024',
            'shipping_country_code' => 'PH',
            'contact_phone' => '+63 917 123 4567',
            'contact_email' => 'purchasing@manilabakery.ph',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'Manila Bakery Supplies Inc.')
            ->assertJsonPath('data.billing_city', 'Makati')
            ->assertJsonPath('data.shipping_city', 'Biñan');

        $this->assertDatabaseHas('buyer_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Manila Bakery Supplies Inc.',
        ]);
    });

    test('authenticated user can update existing buyer profile', function () {
        $user = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $user->id, 'business_name' => 'Old Name']);

        $response = $this->actingAs($user)->putJson('/api/v1/buyer-profile', [
            'business_name' => 'New Business Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'New Business Name');

        $this->assertDatabaseCount('buyer_profiles', 1);
        $this->assertDatabaseHas('buyer_profiles', [
            'user_id' => $user->id,
            'business_name' => 'New Business Name',
        ]);
    });

    test('authenticated user can retrieve their buyer profile', function () {
        $user = User::factory()->create();
        BuyerProfile::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'Test Company',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/buyer-profile');

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'Test Company');
    });

    test('user without profile gets 404 on GET', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/buyer-profile');

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    });

    test('unauthenticated request returns 401', function () {
        $this->getJson('/api/v1/buyer-profile')->assertUnauthorized();
        $this->putJson('/api/v1/buyer-profile', ['business_name' => 'Test'])->assertUnauthorized();
    });

    test('validation requires business_name', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/buyer-profile', [
            'billing_city' => 'Makati',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.fields.business_name.0', 'The business name field is required.');
    });

    test('validation rejects invalid country_code length', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/buyer-profile', [
            'business_name' => 'Test',
            'billing_country_code' => 'PHX',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJson(fn ($json) => $json->has('error.fields.billing_country_code'));
    });

    test('validation rejects invalid contact_email format', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/buyer-profile', [
            'business_name' => 'Test',
            'contact_email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJson(fn ($json) => $json->has('error.fields.contact_email'));
    });
});

describe('Buyer Profile Livewire Component', function () {
    test('component renders for authenticated user', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BuyerProfileForm::class)
            ->assertStatus(200)
            ->assertSee('Buyer Profile');
    });

    test('component loads existing profile data', function () {
        $user = User::factory()->create();
        BuyerProfile::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'Existing Business',
            'billing_city' => 'Quezon City',
        ]);

        Livewire::actingAs($user)
            ->test(BuyerProfileForm::class)
            ->assertSet('business_name', 'Existing Business')
            ->assertSet('billing_city', 'Quezon City');
    });

    test('component saves new buyer profile', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BuyerProfileForm::class)
            ->set('business_name', 'New Buyer Corp')
            ->set('billing_address_1', '123 Main St')
            ->set('billing_city', 'Manila')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('buyer_profiles', [
            'user_id' => $user->id,
            'business_name' => 'New Buyer Corp',
            'billing_city' => 'Manila',
        ]);
    });

    test('component validates business_name is required', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BuyerProfileForm::class)
            ->set('business_name', '')
            ->call('save')
            ->assertHasErrors(['business_name' => 'required']);
    });

    test('component copies billing to shipping when same-as-billing is checked', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BuyerProfileForm::class)
            ->set('business_name', 'Test Co')
            ->set('billing_address_1', '456 Elm St')
            ->set('billing_city', 'Cebu')
            ->set('billing_province', 'Cebu')
            ->set('billing_zip_code', '6000')
            ->set('useShippingSameAsBilling', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = $user->fresh()->buyerProfile;
        expect($profile->shipping_address_1)->toBe('456 Elm St');
        expect($profile->shipping_city)->toBe('Cebu');
    });
});

describe('BuyerProfile Model', function () {
    test('toSnapshot returns correct structure', function () {
        $profile = BuyerProfile::factory()->make([
            'business_name' => 'Snapshot Test Corp',
            'billing_address_1' => '789 Oak Ave',
            'billing_city' => 'Davao',
            'billing_province' => 'Davao del Sur',
            'billing_zip_code' => '8000',
            'billing_country_code' => 'PH',
            'shipping_address_1' => '321 Pine Rd',
            'shipping_city' => 'General Santos',
            'shipping_province' => 'South Cotabato',
            'shipping_zip_code' => '9500',
            'shipping_country_code' => 'PH',
        ]);

        $snapshot = $profile->toSnapshot();

        expect($snapshot)->toHaveKeys(['business_name', 'billing_address', 'shipping_address']);
        expect($snapshot['business_name'])->toBe('Snapshot Test Corp');
        expect($snapshot['billing_address']['city'])->toBe('Davao');
        expect($snapshot['shipping_address']['city'])->toBe('General Santos');
    });

    test('user has hasOne buyerProfile relationship', function () {
        $user = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $user->id]);

        expect($user->buyerProfile)->toBeInstanceOf(BuyerProfile::class);
        expect($user->buyerProfile->user->id)->toBe($user->id);
    });
});

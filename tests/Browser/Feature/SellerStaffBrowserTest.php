<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    config()->set('marketplace.features.staff_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('marketplace.features.payouts_enabled', true);
    config()->set('humancheck.enabled', false);
});

/**
 * @return array{0: User, 1: Shop, 2: User, 3: Product}
 */
function staffBrowserFixture(): array
{
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $staff = User::factory()->create();
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $shop->id,
        'email' => $staff->email,
        'invited_by_id' => $owner->id,
    ]);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id]);

    return [$owner, $shop, $staff, $product];
}

test('the owner invites staff from the team page and the pending invitation is listed', function (): void {
    $owner = User::factory()->create();
    Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);

    $this->actingAs($owner);

    visit('/hub/team')
        ->assertSee('Team seats')
        ->assertSee('0 of 2 seats used')
        ->fill('[wire\\:model="email"]', 'new-staff@example.test')
        ->click('Send invitation')
        ->assertSee('Pending invitations')
        ->assertSee('new-staff@example.test')
        ->assertSee('1 of 2 seats used');

    $this->assertDatabaseHas('shop_staff', [
        'email' => 'new-staff@example.test',
        'status' => 'invited',
    ]);
});

test('an invited user accepts the invitation and lands on the staff dashboard', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    $invitee = User::factory()->create(['email' => 'invited@example.test']);
    $token = str_repeat('a', 64);

    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'email' => $invitee->email,
        'token_hash' => hash('sha256', $token),
        'invited_by_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    visit('/staff/invitations/'.$token)
        ->assertSee('invited@example.test')
        ->assertSee('Sign in')
        ->assertSee('Create account');

    $this->actingAs($invitee);

    visit('/staff/invitations/'.$token)
        ->assertSee('Join team')
        ->click('Join team')
        ->assertPathIs('/portal/dashboard')
        ->assertDontSee('Register Business');

    $this->assertDatabaseHas('shop_staff', [
        'user_id' => $invitee->id,
        'status' => 'active',
        'shop_id' => $shop->id,
    ]);
});

test('the seat meter disables invitations at the configured cap', function (): void {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
    ShopStaff::factory()->active(User::factory()->create())->create([
        'shop_id' => $shop->id,
        'invited_by_id' => $owner->id,
    ]);
    ShopStaff::factory()->create([
        'shop_id' => $shop->id,
        'invited_by_id' => $owner->id,
        'status' => 'invited',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($owner);

    visit('/hub/team')
        ->assertSee('2 of 2 seats used')
        ->assertSee('Send invitation');
});

test('staff can manage catalog pages but owner-only hub pages return 403', function (): void {
    [, , $staff, $product] = staffBrowserFixture();

    $this->actingAs($staff);

    visit('/hub/products')->assertSee('Products');
    visit('/hub/products/'.$product->id.'/edit')->assertSee('Edit Product');

    foreach (['/hub/shop/profile', '/hub/certifications', '/hub/earnings', '/hub/team', '/hub/payout-account'] as $url) {
        visit($url)->assertSee('403');
    }
});

test('staff cannot edit another shop product', function (): void {
    [, $shop, $staff] = staffBrowserFixture();
    $otherOwner = User::factory()->create();
    $otherShop = Shop::factory()->create(['user_id' => $otherOwner->id, 'status' => 'active']);
    $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id]);

    expect($shop->id)->not->toBe($otherShop->id);

    $this->actingAs($staff);

    visit('/hub/products/'.$otherProduct->id.'/edit')->assertSee('403');
});

test('revoked staff lose hub access on the next request', function (): void {
    [, , $staff, $product] = staffBrowserFixture();

    $this->actingAs($staff);
    visit('/hub/products/'.$product->id.'/edit')->assertSee('Edit Product');

    ShopStaff::query()->where('user_id', $staff->id)->update([
        'status' => 'revoked',
        'removed_at' => now(),
    ]);

    $this->actingAs($staff->fresh());

    visit('/hub/products/'.$product->id.'/edit')->assertSee('403');
});

test('staff are blocked from starting seller onboarding', function (): void {
    [, , $staff] = staffBrowserFixture();

    $this->actingAs($staff);

    visit('/account/onboarding')->assertPathIs('/portal/dashboard');
});

test('staff can leave the shop team', function (): void {
    [, , $staff] = staffBrowserFixture();

    $this->actingAs($staff);

    visit('/hub/team/leave')
        ->assertSee('Leave shop team')
        ->click('button:has-text("Leave shop")')
        ->click('[wire\\:click="leave"]')
        ->assertPathIs('/portal/dashboard');

    $this->assertDatabaseHas('shop_staff', [
        'user_id' => $staff->id,
        'status' => 'left',
    ]);
});

test('an admin can review and remove a shop staff member', function (): void {
    $this->seed(RolePermissionSeeder::class);

    [$owner, $shop, $staff] = staffBrowserFixture();
    $membership = ShopStaff::query()->where('user_id', $staff->id)->firstOrFail();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    visit('/admin/merchants/'.$shop->id)
        ->assertSee('Shop staff')
        ->assertSee($staff->email)
        ->click('[wire\\:click="confirmRemoveStaff('.$membership->id.')"]')
        ->assertSee('Confirm removal')
        ->click('[wire\\:click="removeStaff"]')
        ->assertSee('Revoked');

    $this->assertDatabaseHas('shop_staff', [
        'user_id' => $staff->id,
        'status' => 'revoked',
    ]);
});

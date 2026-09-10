<?php

declare(strict_types=1);

use App\Livewire\Portal\Dashboard;
use App\Models\CertificationApplication;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->user->assignRole('seller');
});

// ── bestCertificate computed property ─────────────────────────────────────────

test('bestCertificate is null when shop has no certificates', function () {
    Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);

    $component = Livewire::actingAs($this->user)->test(Dashboard::class);

    expect($component->instance()->bestCertificate())->toBeNull();
});

test('bestCertificate returns the active cert when multiple statuses exist', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);
    $active = ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);

    $component = Livewire::actingAs($this->user)->test(Dashboard::class);

    expect($component->instance()->bestCertificate()->id)->toBe($active->id);
});

test('bestCertificate returns expiring_soon over under_review and expired', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);
    ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);
    $expiringSoon = ShopCertificate::factory()->expiringSoon()->create(['shop_id' => $shop->id]);

    $component = Livewire::actingAs($this->user)->test(Dashboard::class);

    expect($component->instance()->bestCertificate()->id)->toBe($expiringSoon->id);
});

test('bestCertificate returns under_review over expired', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);
    $underReview = ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);

    $component = Livewire::actingAs($this->user)->test(Dashboard::class);

    expect($component->instance()->bestCertificate()->id)->toBe($underReview->id);
});

// ── Halal cert stat card display ──────────────────────────────────────────────

test('halal cert stat card shows dash when shop has no certificates', function () {
    Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSeeHtml('—');
});

test('halal cert stat card shows dash when only expired cert exists', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSeeHtml('—');
});

test('halal cert stat card shows status pill for active cert', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Active')
        ->assertSee('Halal Certification');
});

test('halal cert stat card shows status pill for expiring_soon cert', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->expiringSoon()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Halal Certification');
});

test('halal cert stat card shows status pill for under_review cert', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Halal Certification');
});

// ── Halal cert stat card link target ─────────────────────────────────────────

test('cert stat card links to certifications index when best cert is active', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee(route('hub.certifications.index'), false);
});

test('cert stat card links to apply page when only expired cert exists', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee(route('apply.certificate'), false);
});

test('cert stat card links to apply page when no certs exist', function () {
    Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee(route('apply.certificate'), false);
});

// ── Certification CTA / warning notice ───────────────────────────────────────

test('certification CTA shows when shop has no certs and no application', function () {
    Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('Apply for Halal Certification');
});

test('certification CTA is hidden when shop has any certificate', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    ShopCertificate::factory()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertDontSee('Apply for Halal Certification');
});

test('pending application notice shows when application exists but no certs', function () {
    $shop = Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    CertificationApplication::factory()->create(['shop_id' => $shop->id, 'status' => 'pending']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('certification application is')
        ->assertDontSee('Apply for Halal Certification');
});

// ── RFQ section ───────────────────────────────────────────────────────────────

test('rfq section shows empty state when user has no rfqs', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('No RFQs yet');
});

test('rfq section shows rfq rows when user has submitted rfqs', function () {
    Rfq::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Halal Chicken Bulk Order',
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Halal Chicken Bulk Order');
});

test('rfq row shows admin_status badge from the recipient', function () {
    $rfq = Rfq::factory()->create(['user_id' => $this->user->id]);
    RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'admin_status' => 'in_progress',
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('In Progress');
});

test('rfq row falls back to pending badge when no recipient exists', function () {
    Rfq::factory()->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Pending');
});

test('recentRfqs computed returns at most 5 rfqs', function () {
    Rfq::factory()->count(7)->create(['user_id' => $this->user->id]);

    $component = Livewire::actingAs($this->user)->test(Dashboard::class);

    expect($component->instance()->recentRfqs())->toHaveCount(5);
});

// ── General rendering ─────────────────────────────────────────────────────────

test('dashboard renders for a buyer with no shop', function () {
    $buyer = User::factory()->create(['email_verified_at' => now()]);
    $buyer->assignRole('buyer');

    Livewire::actingAs($buyer)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Register Business');
});

test('dashboard shows view business link for an onboarded seller', function () {
    Shop::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('View Business');
});

<?php

declare(strict_types=1);

use App\Livewire\Admin\CertificationQueue;
use App\Livewire\CompanyHub\CertificationApplicationForm;
use App\Models\CertificationApplication;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('an onboarded seller can express interest in external halal certification', function () {
    Notification::fake();
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($seller);

    Livewire::test(CertificationApplicationForm::class)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('interest in halal certification has been sent');

    $this->assertDatabaseHas('certification_applications', [
        'shop_id' => $shop->id,
        'status' => 'interested',
    ]);

    Notification::assertSentTo($admin, App\Notifications\CertificationInterestNotification::class);
});

test('all admins are notified when a seller expresses certification interest', function () {
    Notification::fake();
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id]);
    $admins = User::factory()->count(2)->create();
    $admins->each(fn (User $admin) => $admin->assignRole('admin'));

    $this->actingAs($seller);

    Livewire::test(CertificationApplicationForm::class)->call('submit');

    $admins->each(fn (User $admin) => Notification::assertSentTo($admin, App\Notifications\CertificationInterestNotification::class));
});

test('a seller cannot submit duplicate certification interest', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    CertificationApplication::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($seller);

    Livewire::test(CertificationApplicationForm::class)
        ->call('submit')
        ->assertSee('interest has already been sent');

    expect(CertificationApplication::where('shop_id', $shop->id)->count())->toBe(1);
});

test('an admin can mark a seller certification interest as contacted', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $application = CertificationApplication::factory()->create();

    $this->actingAs($admin);

    Livewire::test(CertificationQueue::class)
        ->call('markContacted', $application->id)
        ->assertHasNoErrors()
        ->assertSee('Seller marked as contacted');

    $this->assertDatabaseHas('certification_applications', [
        'id' => $application->id,
        'status' => 'contacted',
        'addressed_by' => $admin->id,
    ]);
});

test('the admin queue does not expose approval or rejection actions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    CertificationApplication::factory()->create();

    $this->actingAs($admin);

    Livewire::test(CertificationQueue::class)
        ->assertSee('Mark as Contacted')
        ->assertDontSee('Approve')
        ->assertDontSee('Reject');
});

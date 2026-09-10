<?php

declare(strict_types=1);

use App\Livewire\Admin\CertificationQueue;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\User;
use App\Notifications\CertificateApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'seller']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('renders the certification queue for admins', function () {
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->assertStatus(200)
        ->assertSee('Halal Certifications')
        ->assertSee($shop->registered_business_name)
        ->assertSee('Under Review');
});

it('shows the under_review status filter option', function () {
    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->assertSee('Under Review');
});

it('filters certificates by under_review status', function () {
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);

    $reviewCert = ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);
    $activeCert = ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);

    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->set('statusFilter', 'under_review')
        ->assertSee($reviewCert->expiry_date->format('M d, Y'))
        ->assertDontSee($activeCert->expiry_date->format('M d, Y'));
});

it('approves a certificate and transitions status to active', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $cert = ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->call('approveCertificate', $cert->id);

    $cert->refresh();
    expect($cert->status)->toBe('active')
        ->and($cert->is_verified)->toBeTrue();
});

it('sends CertificateApprovedNotification to the shop owner on approve', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $cert = ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->call('approveCertificate', $cert->id);

    Notification::assertSentTo($seller, CertificateApprovedNotification::class);
});

it('closes the document viewer after approving a certificate', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $cert = ShopCertificate::factory()->underReview()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->set('showDocumentViewer', true)
        ->set('viewingCertificateId', $cert->id)
        ->call('approveCertificate', $cert->id)
        ->assertSet('showDocumentViewer', false)
        ->assertSet('viewingCertificateId', null);
});

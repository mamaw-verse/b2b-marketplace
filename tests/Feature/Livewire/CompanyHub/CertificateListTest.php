<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\CertificateList;
use App\Models\Media;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\User;
use App\Notifications\CertificateSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('renders the certificate list for the seller shop', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $cert = ShopCertificate::factory()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->assertStatus(200)
        ->assertSee('Certificates')
        ->assertSee($cert->expiry_date->format('M d, Y'))
        ->assertSee('Active');
});

it('lists each certificate with its expiry date and status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $active = ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);
    $expiring = ShopCertificate::factory()->expiringSoon()->create(['shop_id' => $shop->id]);
    $expired = ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->assertSee($active->expiry_date->format('M d, Y'))
        ->assertSee($expiring->expiry_date->format('M d, Y'))
        ->assertSee($expired->expiry_date->format('M d, Y'))
        ->assertSee('Expiring Soon')
        ->assertSee('Expired');
});

it('renders an apply for certificate button', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->assertSee(route('apply.certificate'));
});

it('renders an empty state when the shop has no certificates', function () {
    $user = User::factory()->create();
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->assertSee('No certificates yet');
});

it('returns 404 when the user has no shop', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->assertStatus(404);
});

it('opens the add certificate modal and resets the form fields', function () {
    $user = User::factory()->create();
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->call('openAddModal')
        ->assertSet('showAddModal', true)
        ->assertSet('expiry_date', '');
});

it('uploads the certificate file on selection', function () {
    Config::set('services.clamav.enabled', true);
    Storage::fake('s3');
    Queue::fake();

    $user = User::factory()->create();
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $file = UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->call('openAddModal')
        ->set('certificateFile', $file)
        ->assertSet('uploadedMediaId', fn ($id) => is_int($id))
        ->assertSet('uploadedScanStatus', 'pending');

    expect(Media::count())->toBe(1);
});

it('saves a new certificate with under_review status and notifies admins', function () {
    Notification::fake();

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $media = Media::factory()->create(['scan_status' => 'clean']);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->call('openAddModal')
        ->set('expiry_date', now()->addYear()->format('Y-m-d'))
        ->set('uploadedMediaId', $media->id)
        ->set('uploadedFilename', 'cert.pdf')
        ->set('uploadedScanStatus', 'clean')
        ->call('saveCertificate')
        ->assertHasNoErrors()
        ->assertSet('showAddModal', false);

    $cert = ShopCertificate::first();
    expect($cert)->not->toBeNull()
        ->and($cert->shop_id)->toBe($shop->id)
        ->and($cert->expiry_date->format('Y-m-d'))->toBe(now()->addYear()->format('Y-m-d'))
        ->and($cert->media_id)->toBe($media->id)
        ->and($cert->status)->toBe('under_review')
        ->and($cert->is_verified)->toBeFalse();

    Notification::assertSentTo($admin, CertificateSubmittedNotification::class);
});

it('rejects saving without a certificate file', function () {
    $user = User::factory()->create();
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->call('openAddModal')
        ->set('expiry_date', now()->addYear()->format('Y-m-d'))
        ->call('saveCertificate')
        ->assertHasErrors(['certificateFile']);

    expect(ShopCertificate::count())->toBe(0);
});

it('requires an expiry date', function () {
    $user = User::factory()->create();
    Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $media = Media::factory()->create(['scan_status' => 'clean']);

    Livewire::actingAs($user)
        ->test(CertificateList::class)
        ->call('openAddModal')
        ->set('uploadedMediaId', $media->id)
        ->set('uploadedFilename', 'cert.pdf')
        ->set('uploadedScanStatus', 'clean')
        ->call('saveCertificate')
        ->assertHasErrors(['expiry_date']);
});

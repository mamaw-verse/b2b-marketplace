<?php

declare(strict_types=1);

use App\Livewire\Admin\CertificationQueue;
use App\Livewire\Storefront\ApplyForCertificate;
use App\Mail\CertificationApplicationAcknowledgment;
use App\Models\CertificationApplication;
use App\Models\ShopCertificate;
use App\Models\User;
use App\Notifications\CertificationInterestNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
    Notification::fake();
    Mail::fake();
});

test('a public visitor can apply for halal certification', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $component = Livewire::test(ApplyForCertificate::class);

    $component
        ->set('company_name', 'Acme Foods Inc.')
        ->set('email', 'contact@acme.com')
        ->set('document', UploadedFile::fake()->create('loi.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->set('turnstile_token', 'valid-token')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(CertificationApplication::count())->toBe(1)
        ->and(CertificationApplication::latest()->first())
        ->shop_id->toBeNull()
        ->email->toBe('contact@acme.com')
        ->company_name->toBe('Acme Foods Inc.')
        ->status->toBe('interested')
        ->and(CertificationApplication::first()->document_path)->not->toBeNull();

    Notification::assertSentTo($admin, CertificationInterestNotification::class);
    Mail::assertQueued(CertificationApplicationAcknowledgment::class, 1);
});

test('all admins are notified when a public visitor applies', function () {
    $admins = User::factory()->count(2)->create();
    $admins->each(fn (User $admin) => $admin->assignRole('admin'));

    $component = Livewire::test(ApplyForCertificate::class);

    $component
        ->set('company_name', 'Test Co.')
        ->set('email', 'test@example.com')
        ->set('document', UploadedFile::fake()->create('loi.pdf', 100, 'application/pdf'))
        ->set('turnstile_token', 'valid-token')
        ->call('submit');

    $admins->each(fn (User $admin) => Notification::assertSentTo($admin, CertificationInterestNotification::class));
});

test('validation fails when required fields are missing', function () {
    Livewire::test(ApplyForCertificate::class)
        ->set('company_name', '')
        ->set('email', '')
        ->set('turnstile_token', '')
        ->call('submit')
        ->assertHasErrors(['email', 'company_name', 'document', 'turnstile_token'])
        ->assertHasErrors(['turnstile_token' => 'Please complete the security check.']);
});

test('validation fails when the captcha token is invalid', function () {
    Livewire::test(ApplyForCertificate::class)
        ->set('company_name', 'Acme')
        ->set('email', 'a@b.com')
        ->set('document', UploadedFile::fake()->create('loi.pdf', 100, 'application/pdf'))
        ->set('turnstile_token', 'invalid-token')
        ->call('submit')
        ->assertHasErrors(['turnstile_token']);
});

test('honeypot submission is silently discarded', function () {
    Livewire::test(ApplyForCertificate::class)
        ->set('company_name', 'Bot')
        ->set('email', 'bot@spam.com')
        ->set('website', 'http://spamsite.com')
        ->set('document', UploadedFile::fake()->create('loi.pdf', 100, 'application/pdf'))
        ->set('turnstile_token', 'valid-token')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(CertificationApplication::count())->toBe(0);
    Notification::assertNothingSent();
});

test('the admin sees the halal certifications table with document view action', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $certificate = ShopCertificate::factory()->create();

    $this->actingAs($admin);

    Livewire::test(CertificationQueue::class)
        ->assertSee('Halal Certifications')
        ->assertSee($certificate->shop->registered_business_name)
        ->assertSee($certificate->expiry_date->format('M d, Y'))
        ->assertSee('View')
        ->assertSee('Halal Certificate Applicants');
});

test('the admin queue is a read-only log without actions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $application = CertificationApplication::factory()->public()->create();
    Storage::disk('s3')->put($application->document_path, 'loi contents');

    $this->actingAs($admin);

    Livewire::test(CertificationQueue::class)
        ->assertSee($application->company_name)
        ->assertSee('Download')
        ->assertDontSee('View Document')
        ->assertDontSee('Mark as Contacted');
});

test('previewable uploaded documents show a view button instead of download', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    CertificationApplication::factory()->create([
        'document_path' => 'certification-applications/2026/09/07/loi.pdf',
    ]);

    $this->actingAs($admin);

    Livewire::test(CertificationQueue::class)
        ->assertSee('View Document')
        ->assertDontSee('Download');
});

test('an admin can download an application document through the app', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $application = CertificationApplication::factory()->public()->create();
    Storage::disk('s3')->put($application->document_path, 'loi contents');
    $filename = basename($application->document_path);

    $this->actingAs($admin);

    $response = $this->get(route('admin.certifications.applications.document', [
        'application' => $application->id,
        'download' => '1',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");

    $inline = $this->get(route('admin.certifications.applications.document', $application->id));
    $inline->assertHeader('Content-Disposition', "inline; filename=\"{$filename}\"");
});

test('the admin can filter and search halal certifications', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $active = ShopCertificate::factory()->create(['status' => 'active']);
    $expired = ShopCertificate::factory()->expired()->create();

    $this->actingAs($admin);

    Livewire::test(CertificationQueue::class)
        ->assertSee($active->shop->registered_business_name)
        ->assertSee($expired->shop->registered_business_name)
        ->set('statusFilter', 'expired')
        ->assertSee($expired->shop->registered_business_name)
        ->assertDontSee($active->shop->registered_business_name)
        ->set('statusFilter', '')
        ->set('search', $active->shop->registered_business_name)
        ->assertSee($active->shop->registered_business_name)
        ->assertDontSee($expired->shop->registered_business_name);
});

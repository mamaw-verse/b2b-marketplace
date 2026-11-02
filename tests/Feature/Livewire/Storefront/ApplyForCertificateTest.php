<?php

declare(strict_types=1);

use App\Livewire\Storefront\ApplyForCertificate;
use App\Mail\CertificationApplicationAcknowledgment;
use App\Models\CertificationApplication;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\CertificationInterestNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('s3');
    Mail::fake();
    Notification::fake();
    Role::firstOrCreate(['name' => 'admin']);
    config(['humancheck.enabled' => false]);
});

it('renders the apply for certificate component', function () {
    Livewire::test(ApplyForCertificate::class)
        ->assertStatus(200)
        ->assertSee('Apply for Certificate')
        ->assertSee('Business Name')
        ->assertSee('Email Address');
});

it('prefills business name and email for a logged-in user', function () {
    $user = User::factory()->create();
    Shop::factory()->create([
        'user_id' => $user->id,
        'registered_business_name' => 'Acme Foods Inc.',
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test(ApplyForCertificate::class)
        ->assertSet('email', $user->email)
        ->assertSet('company_name', 'Acme Foods Inc.');
});

it('rejects submissions if the honeypot is filled', function () {
    Livewire::test(ApplyForCertificate::class)
        ->set('website', 'http://spam.com')
        ->call('submit')
        ->assertSet('submitted', true); // Pretends it succeeded

    expect(CertificationApplication::count())->toBe(0);

    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('rejects submissions when human check is enabled and pass is missing', function () {
    config(['humancheck.enabled' => true]);

    $component = Livewire::test(ApplyForCertificate::class);

    $component->set('company_name', 'Test Corp')
        ->set('email', 'test@test.com')
        ->set('document', UploadedFile::fake()->create('loi.pdf', 100, 'application/pdf'))
        ->call('submit')
        ->assertHasErrors(['human_check']);

    expect(CertificationApplication::count())->toBe(0);
});

it('creates a certification application and sends notifications', function () {
    // Create an admin to receive notifications
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $component = Livewire::test(ApplyForCertificate::class);

    $file = UploadedFile::fake()->create('loi.pdf', 100, 'application/pdf');

    $component->set('company_name', 'Test Corp')
        ->set('email', 'test@test.com')
        ->set('document', $file)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('submittedEmail', 'test@test.com');

    $application = CertificationApplication::first();

    expect($application)->not->toBeNull()
        ->and($application->company_name)->toBe('Test Corp')
        ->and($application->email)->toBe('test@test.com')
        ->and($application->status)->toBe('interested')
        ->and($application->shop_id)->toBeNull();

    // Verify file was uploaded
    Storage::disk('s3')->assertExists($application->document_path);

    // Verify applicant email, including the uploaded document as an attachment
    Mail::assertQueued(CertificationApplicationAcknowledgment::class, function (CertificationApplicationAcknowledgment $mail) use ($application) {
        if (! $mail->hasTo('test@test.com') || $mail->application->id !== $application->id) {
            return false;
        }

        $attachments = $mail->attachments();

        return count($attachments) === 1
            && $attachments[0]->as === 'letter_of_intent_test_corp.pdf';
    });

    // Verify admin notification
    Notification::assertSentTo(
        [$admin],
        CertificationInterestNotification::class,
        function ($notification) use ($application) {
            return $notification->application->id === $application->id;
        }
    );
});

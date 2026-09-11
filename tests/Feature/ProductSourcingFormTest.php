<?php

declare(strict_types=1);

use App\Livewire\Storefront\ProductSourcingForm;
use App\Mail\SourcingRequestAcknowledgment;
use App\Models\SourcingRequest;
use App\Models\User;
use App\Notifications\SourcingRequestReceivedNotification;
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

test('the sourcing request page renders the form', function () {
    $this->get(route('sourcing-request.create'))
        ->assertOk()
        ->assertSee('Can’t Find What You’re Looking For?')
        ->assertSee('We can source it for you.')
        ->assertSee('How It Works')
        ->assertSee('Your Details')
        ->assertSee('Product Name / Type')
        ->assertSee('Submit Sourcing Request');
});

test('a public visitor can submit a sourcing request', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $component = Livewire::test(ProductSourcingForm::class);

    $component
        ->set('name', 'John Smith')
        ->set('email', 'john@example.com')
        ->set('product_name', 'Bulk turmeric powder')
        ->set('product_description', 'High curcumin content, need about 500kg.')
        ->set('turnstile_token', 'valid-token')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(SourcingRequest::count())->toBe(1)
        ->and(SourcingRequest::latest()->first())
        ->name->toBe('John Smith')
        ->email->toBe('john@example.com')
        ->product_name->toBe('Bulk turmeric powder')
        ->product_description->toBe('High curcumin content, need about 500kg.')
        ->user_id->toBeNull()
        ->status->toBe('pending');

    Notification::assertSentTo($admin, SourcingRequestReceivedNotification::class);
    Mail::assertQueued(SourcingRequestAcknowledgment::class, 1);
});

test('a logged-in user gets name and email prefilled', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(ProductSourcingForm::class)
        ->assertSet('name', 'Jane Doe')
        ->assertSet('email', 'jane@example.com');
});

test('product name is prefilled when provided', function () {
    Livewire::test(ProductSourcingForm::class, ['prefillProductName' => 'Organic Honey'])
        ->assertSet('product_name', 'Organic Honey');
});

test('validation fails when required fields are missing', function () {
    Livewire::test(ProductSourcingForm::class)
        ->set('name', '')
        ->set('email', '')
        ->set('product_name', '')
        ->set('turnstile_token', '')
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'product_name', 'turnstile_token'])
        ->assertHasErrors(['turnstile_token' => 'Please complete the security check.']);
});

test('validation fails when captcha token is invalid', function () {
    Livewire::test(ProductSourcingForm::class)
        ->set('name', 'John')
        ->set('email', 'john@example.com')
        ->set('product_name', 'Coffee beans')
        ->set('turnstile_token', 'invalid-token')
        ->call('submit')
        ->assertHasErrors(['turnstile_token']);
});

test('honeypot submission is silently discarded', function () {
    Livewire::test(ProductSourcingForm::class)
        ->set('name', 'Bot')
        ->set('email', 'bot@spam.com')
        ->set('product_name', 'Spam')
        ->set('honeypot', 'http://spamsite.com')
        ->set('turnstile_token', 'valid-token')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(SourcingRequest::count())->toBe(0);
    Notification::assertNothingSent();
    Mail::assertNothingQueued();
});

test('reference file is stored to s3 when upload provided', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::test(ProductSourcingForm::class)
        ->set('name', 'John')
        ->set('email', 'john@example.com')
        ->set('product_name', 'Honey')
        ->set('reference_file', UploadedFile::fake()->image('reference.jpg'))
        ->set('turnstile_token', 'valid-token')
        ->call('submit');

    $request = SourcingRequest::first();

    expect($request)->not->toBeNull()
        ->and($request->reference_file_path)->not->toBeNull();

    Storage::disk('s3')->assertExists($request->reference_file_path);
});

test('reference file is optional', function () {
    Livewire::test(ProductSourcingForm::class)
        ->set('name', 'John')
        ->set('email', 'john@example.com')
        ->set('product_name', 'Honey')
        ->set('turnstile_token', 'valid-token')
        ->call('submit');

    expect(SourcingRequest::first()->reference_file_path)->toBeNull();
});

test('success state is shown after submission', function () {
    Livewire::test(ProductSourcingForm::class)
        ->set('name', 'John')
        ->set('email', 'john@example.com')
        ->set('product_name', 'Honey')
        ->set('turnstile_token', 'valid-token')
        ->call('submit')
        ->assertSet('submitted', true)
        ->assertSee('Request Submitted')
        ->assertDontSee('Can\'t find the product you need');
});

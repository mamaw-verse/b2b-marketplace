<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
});

test('guests cannot access the settings page', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('the single settings page loads directly without password confirmation', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
});

test('the single settings page renders profile, appearance and security sections', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('profile.edit'));

    $response->assertOk()
        ->assertSee('Phone number')
        ->assertSee('Profile photo')
        ->assertSee('Appearance')
        ->assertSee('Update password')
        ->assertSee('Two-factor authentication');
});

test('the portal nav shows a single settings link with section anchors', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('profile.edit'));

    $response->assertOk()
        ->assertSee(route('profile.edit').'#profile', false)
        ->assertSee(route('profile.edit').'#appearance', false)
        ->assertSee(route('profile.edit').'#security', false);
});

test('old security and appearance urls redirect to the single settings page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('security.edit'))
        ->assertRedirect(route('profile.edit'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('appearance.edit'))
        ->assertRedirect(route('profile.edit'));
});

test('profile update saves phone number', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'phone' => null]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('phone', '+63 917 123 4567')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->phone)->toBe('+63 917 123 4567');
});

test('profile photo upload stores the file and clear plus save removes it', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->image('avatar.jpg'))
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $path = $user->fresh()->photo_path;

    expect($path)->not->toBeNull();
    Storage::disk('s3')->assertExists($path);

    Livewire::actingAs($user->fresh())
        ->test('pages::settings.profile')
        ->call('clearPhoto')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->photo_path)->toBeNull();
    Storage::disk('s3')->assertMissing($path);
});

test('the user photo url accessor resolves to the proxied photo route', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    expect($user->photo_url)->toBeNull();

    Storage::disk('s3')->put('profile-photos/avatar.jpg', 'fake-image-bytes');
    $user->update(['photo_path' => 'profile-photos/avatar.jpg']);

    expect($user->fresh()->photo_url)->toBe(route('users.photo', $user));
});

test('password can be updated from the single settings page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('current_password', 'password')
        ->set('password', 'new-secure-password-1')
        ->set('password_confirmation', 'new-secure-password-1')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-secure-password-1', $user->fresh()->password))->toBeTrue();
});
